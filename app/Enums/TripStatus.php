<?php

namespace App\Enums;

enum TripStatus: string
{
    case Draft = 'draft';
    case New = 'new';
    case Reserved = 'reserved';
    case ReadyForDispatch = 'ready_for_dispatch';
    case SearchingDriver = 'searching_driver';
    case OfferSent = 'offer_sent';
    case Assigned = 'assigned';
    case Accepted = 'accepted';
    case EnRoute = 'en_route';
    case AtPickup = 'at_pickup';
    case Waiting = 'waiting';
    case PassengerOnBoard = 'passenger_on_board';
    case InProgress = 'in_progress';
    case AtStop = 'at_stop';
    case AtDestination = 'at_destination';
    case Finishing = 'finishing';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case NoShow = 'no_show';
    case ManualReview = 'manual_review';
    case TechnicalIssue = 'technical_issue';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Entwurf',
            self::New => 'Neu',
            self::Reserved => 'Vorbestellt',
            self::ReadyForDispatch => 'Bereit zur Disposition',
            self::SearchingDriver => 'Fahrersuche',
            self::OfferSent => 'Angebot gesendet',
            self::Assigned => 'Zugewiesen',
            self::Accepted => 'Angenommen',
            self::EnRoute => 'Anfahrt',
            self::AtPickup => 'Am Abholort',
            self::Waiting => 'Wartet',
            self::PassengerOnBoard => 'Fahrgast aufgenommen',
            self::InProgress => 'Fahrt läuft',
            self::AtStop => 'Zwischenstopp',
            self::AtDestination => 'Am Ziel',
            self::Finishing => 'Abschluss',
            self::Completed => 'Abgeschlossen',
            self::Cancelled => 'Storniert',
            self::NoShow => 'No-Show',
            self::ManualReview => 'Manuelle Klärung',
            self::TechnicalIssue => 'Technische Störung',
        };
    }

    public function terminal(): bool
    {
        return in_array($this, [self::Completed, self::Cancelled, self::NoShow], true);
    }
}
