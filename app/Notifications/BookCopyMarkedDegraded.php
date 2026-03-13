<?php

namespace App\Notifications;

use App\Models\BookCopy;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookCopyMarkedDegraded extends Notification
{
    use Queueable;

    public function __construct(private readonly BookCopy $bookCopy)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $book = $this->bookCopy->book()->first();

        return (new MailMessage)
            ->subject('Alerte: exemplaire dégradé')
            ->line('Un exemplaire vient d\'être marqué comme dégradé.')
            ->line('Référence: '.$this->bookCopy->reference_code)
            ->line('Livre: '.($book?->title ?? 'N/A'))
            ->line('État: '.$this->bookCopy->physical_state)
            ->line('Notes: '.($this->bookCopy->notes ?? 'Aucune'));
    }
}
