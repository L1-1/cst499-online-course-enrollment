<?php
declare(strict_types=1);

final class EnrollmentService
{
    public function __construct(private PDO $db) {}

    private function query(string $sql, array $params = []): PDOStatement
    {
        $statement = $this->db->prepare($sql);
        $statement->execute($params);
        return $statement;
    }

    // Every seat-changing operation obtains the same offering lock first.
    private function transaction(int $offeringId, callable $operation): mixed
    {
        $this->db->beginTransaction();
        try {
            $offering = $this->query('SELECT * FROM offerings WHERE id = ? FOR UPDATE', [$offeringId])->fetch();
            if (!$offering) { throw new DomainException('Course offering not found.'); }
            $this->refreshOffers($offering);
            $result = $operation($offering);
            $this->db->commit();
            return $result;
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) { $this->db->rollBack(); }
            throw $error;
        }
    }

    private function remaining(array $offering): int
    {
        $enrolled = (int) $this->query('SELECT COUNT(*) FROM enrollments WHERE offering_id = ?', [$offering['id']])->fetchColumn();
        $reserved = (int) $this->query("SELECT COUNT(*) FROM seat_offers WHERE offering_id = ? AND status = 'pending'", [$offering['id']])->fetchColumn();
        return (int) $offering['capacity'] - $enrolled - $reserved;
    }

    private function closeOffer(array $offer, string $status): void
    {
        $this->query('UPDATE seat_offers SET status = ?, waiting_id = NULL WHERE id = ?', [$status, $offer['id']]);
        $this->query('DELETE FROM waiting_list WHERE id = ?', [$offer['waiting_id']]);
    }

    // Expiration, reservation, notice, and outbox changes commit together.
    private function refreshOffers(array $offering): void
    {
        $expired = $this->query("SELECT * FROM seat_offers WHERE offering_id = ? AND status = 'pending' AND expires_at <= UTC_TIMESTAMP(6)", [$offering['id']])->fetchAll();
        foreach ($expired as $offer) { $this->closeOffer($offer, 'expired'); }
        while ($this->remaining($offering) > 0) {
            $entry = $this->query('SELECT w.* FROM waiting_list w LEFT JOIN seat_offers s ON s.waiting_id = w.id WHERE w.offering_id = ? AND s.id IS NULL ORDER BY w.joined_at, w.id LIMIT 1', [$offering['id']])->fetch();
            if (!$entry) { break; }
            $this->query("INSERT INTO seat_offers (offering_id,recipient_id,waiting_id,expires_at,notice) VALUES (?,?,?,DATE_ADD(UTC_TIMESTAMP(6), INTERVAL 24 HOUR),?)", [$offering['id'], $entry['account_id'], $entry['id'], 'A seat is reserved for you. Accept within 24 hours using the link in My waiting list.']);
            $offerId = (int) $this->db->lastInsertId();
            $this->query('INSERT INTO notification_tasks (offer_id) VALUES (?)', [$offerId]);
        }
    }

    public function enroll(int $accountId, int $offeringId): string
    {
        return $this->transaction($offeringId, function (array $offering) use ($accountId, $offeringId) {
            if ($this->query('SELECT id FROM enrollments WHERE account_id = ? AND offering_id = ?', [$accountId, $offeringId])->fetch()) {
                return 'You are already registered for this course.';
            }
            if ($this->query('SELECT id FROM waiting_list WHERE account_id = ? AND offering_id = ?', [$accountId, $offeringId])->fetch()) {
                return 'You are on the waiting list. Review any seat offer there.';
            }
            if ($this->remaining($offering) < 1) { return 'This course is full. You may join the waiting list.'; }
            $this->query('INSERT INTO enrollments (account_id,offering_id) VALUES (?,?)', [$accountId, $offeringId]);
            return 'Course registered successfully.';
        });
    }

    public function cancel(int $accountId, int $offeringId): string
    {
        return $this->transaction($offeringId, function (array $offering) use ($accountId, $offeringId) {
            $deleted = $this->query('DELETE FROM enrollments WHERE account_id = ? AND offering_id = ?', [$accountId, $offeringId])->rowCount();
            $this->refreshOffers($offering);
            return $deleted ? 'Course removed from your schedule.' : 'No active enrollment was changed.';
        });
    }

    public function join(int $accountId, int $offeringId): string
    {
        return $this->transaction($offeringId, function (array $offering) use ($accountId, $offeringId) {
            if ($this->query('SELECT id FROM enrollments WHERE account_id = ? AND offering_id = ?', [$accountId, $offeringId])->fetch()) {
                return 'You are already registered for this course.';
            }
            if ($this->query('SELECT id FROM waiting_list WHERE account_id = ? AND offering_id = ?', [$accountId, $offeringId])->fetch()) {
                return 'You are already on this waiting list.';
            }
            if ($this->remaining($offering) > 0) { return 'A seat is available. Register from the course catalog.'; }
            $this->query('INSERT INTO waiting_list (account_id,offering_id) VALUES (?,?)', [$accountId, $offeringId]);
            return 'You joined the waiting list. Your position is shown below.';
        });
    }

    public function leave(int $accountId, int $offeringId): string
    {
        return $this->transaction($offeringId, function (array $offering) use ($accountId, $offeringId) {
            $offer = $this->query("SELECT * FROM seat_offers WHERE recipient_id = ? AND offering_id = ? AND status = 'pending'", [$accountId, $offeringId])->fetch();
            if ($offer) { $this->closeOffer($offer, 'declined'); }
            $this->query('DELETE FROM waiting_list WHERE account_id = ? AND offering_id = ?', [$accountId, $offeringId]);
            $this->refreshOffers($offering);
            return 'You left the waiting list.';
        });
    }

    public function respond(int $accountId, int $offerId, bool $accept): string
    {
        $offer = $this->query('SELECT * FROM seat_offers WHERE id = ? AND recipient_id = ?', [$offerId, $accountId])->fetch();
        if (!$offer) { throw new DomainException('Seat offer not found.'); }
        return $this->transaction((int) $offer['offering_id'], function (array $offering) use ($accountId, $offerId, $accept) {
            $offer = $this->query('SELECT * FROM seat_offers WHERE id = ? AND recipient_id = ?', [$offerId, $accountId])->fetch();
            if ($offer['status'] !== 'pending') { return 'This seat offer is no longer pending.'; }
            if ($accept) {
                $this->query('INSERT INTO enrollments (account_id,offering_id) VALUES (?,?)', [$accountId, $offering['id']]);
            }
            $this->closeOffer($offer, $accept ? 'accepted' : 'declined');
            $this->refreshOffers($offering);
            return $accept ? 'Seat offer accepted. You are registered.' : 'Seat offer declined.';
        });
    }

    public function refreshAll(): void
    {
        foreach ($this->query('SELECT id FROM offerings ORDER BY id')->fetchAll() as $row) {
            $this->transaction((int) $row['id'], static fn(array $offering) => null);
        }
    }

    public function semesters(): array
    {
        return $this->query("SELECT * FROM semesters ORDER BY year, FIELD(term,'Spring','Summer','Fall')")->fetchAll();
    }

    public function catalog(int $accountId, int $semesterId): array
    {
        return $this->query("SELECT o.*,c.code,c.title,c.credits,s.year,s.term,
          o.capacity - (SELECT COUNT(*) FROM enrollments e WHERE e.offering_id=o.id)
          - (SELECT COUNT(*) FROM seat_offers f WHERE f.offering_id=o.id AND f.status='pending') AS remaining,
          EXISTS(SELECT 1 FROM enrollments e WHERE e.offering_id=o.id AND e.account_id=?) AS enrolled,
          EXISTS(SELECT 1 FROM waiting_list w WHERE w.offering_id=o.id AND w.account_id=?) AS waiting
          FROM offerings o JOIN courses c ON c.id=o.course_id JOIN semesters s ON s.id=o.semester_id
          WHERE o.semester_id=? ORDER BY c.code", [$accountId,$accountId,$semesterId])->fetchAll();
    }

    public function schedule(int $accountId, int $semesterId): array
    {
        return $this->query('SELECT e.*,c.code,c.title,c.credits,o.section,s.term,s.year FROM enrollments e JOIN offerings o ON o.id=e.offering_id JOIN courses c ON c.id=o.course_id JOIN semesters s ON s.id=o.semester_id WHERE e.account_id=? AND o.semester_id=? ORDER BY c.code', [$accountId,$semesterId])->fetchAll();
    }

    public function waiting(int $accountId): array
    {
        return $this->query("SELECT w.*,c.code,c.title,s.term,s.year,f.id AS offer_id,f.expires_at,f.notice,
          (SELECT COUNT(*) FROM waiting_list q WHERE q.offering_id=w.offering_id AND (q.joined_at<w.joined_at OR (q.joined_at=w.joined_at AND q.id<=w.id))) AS position
          FROM waiting_list w JOIN offerings o ON o.id=w.offering_id JOIN courses c ON c.id=o.course_id JOIN semesters s ON s.id=o.semester_id
          LEFT JOIN seat_offers f ON f.waiting_id=w.id AND f.status='pending' WHERE w.account_id=? ORDER BY s.year,s.term,c.code", [$accountId])->fetchAll();
    }
}
