<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Batch;

class BatchNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    //
    public Batch $batch;

    /**
     * Create a new message instance.
     */
    public function __construct(Batch $batch)
    {
        //
        $this->batch = $batch;
    }

    public function build()
    {
        return $this->subject('New Claim Batch Ready for Processing')
                    ->view('emails.batch_notification')
                    ->with([
                        'batch' => $this->batch,
                    ]);
    }
}
