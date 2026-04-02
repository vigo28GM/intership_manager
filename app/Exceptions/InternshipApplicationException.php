<?php

namespace App\Exceptions;

use Exception;

class InternshipApplicationException extends Exception
{
    public const USER_NOT_FOUND = 'USER_NOT_FOUND';
    public const INTERNSHIP_NOT_FOUND = 'INTERNSHIP_NOT_FOUND';
    public const INTERNSHIP_NOT_VALID = 'INTERNSHIP_NOT_VALID';
    public const USER_NOT_ALLOWED = 'USER_NOT_ALLOWED';
    public const APPLICATION_ALREADY_EXISTS = 'APPLICATION_ALREADY_EXISTS';
    public const GROUP_MISMATCH = 'GROUP_MISMATCH';

    private int $httpCode;
    private string $errorCode;

    /**
     * Izveido exception, kad lietotājs nav atrasts datubāzē.
     */
    public static function userNotFound(int $userId): self
    {
        $e = new self("Lietotājs ar ID {$userId} nav atrasts datubāzē.");
        $e->httpCode = 404;
        $e->errorCode = self::USER_NOT_FOUND;
        return $e;
    }

    /**
     * Izveido exception, kad prakse nav atrasta.
     */
    public static function internshipNotFound(int $internshipId): self
    {
        $e = new self("Prakse ar ID {$internshipId} nav atrasta.");
        $e->httpCode = 404;
        $e->errorCode = self::INTERNSHIP_NOT_FOUND;
        return $e;
    }

    /**
     * Izveido exception, kad prakse nav derīga (nav aktīvs laika periods).
     */
    public static function internshipNotValid(int $internshipId): self
    {
        $e = new self("Prakse ar ID {$internshipId} nav derīga vai nav aktīvs laika periods.");
        $e->httpCode = 400;
        $e->errorCode = self::INTERNSHIP_NOT_VALID;
        return $e;
    }

    /**
     * Izveido exception, kad lietotājam nav atļauts pieteikties šajā praksē.
     */
    public static function userNotAllowed(int $userId, int $internshipId): self
    {
        $e = new self("Lietotājam ar ID {$userId} nav atļauts pieteikties praksei ar ID {$internshipId}.");
        $e->httpCode = 403;
        $e->errorCode = self::USER_NOT_ALLOWED;
        return $e;
    }

    /**
     * Izveido exception, kad pieteikums jau eksistē.
     */
    public static function applicationAlreadyExists(int $userId, int $internshipId): self
    {
        $e = new self("Lietotājs ar ID {$userId} jau ir pieteicies praksei ar ID {$internshipId}.");
        $e->httpCode = 409;
        $e->errorCode = self::APPLICATION_ALREADY_EXISTS;
        return $e;
    }

    /**
     * Izveido exception, kad nosūtītā group_id neatbilst lietotāja group_id.
     */
    public static function groupMismatch(int $sentGroupId, int $actualGroupId): self
    {
        $e = new self("Nosūtītā group_id ({$sentGroupId}) neatbilst lietotāja group_id ({$actualGroupId}).");
        $e->httpCode = 400;
        $e->errorCode = self::GROUP_MISMATCH;
        return $e;
    }

    /**
     * Get the HTTP status code for API responses.
     */
    public function getHttpCode(): int
    {
        return $this->httpCode;
    }

    /**
     * Get the error code for API responses.
     */
    public function getErrorCode(): string
    {
        return $this->errorCode ?? 'UNKNOWN_ERROR';
    }
}
