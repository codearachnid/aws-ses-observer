<?php

declare(strict_types=1);

namespace codearachnid\AwsSesObserver\Actions;

use Aws\Sns\Message as SnsMessage;
use Aws\Sns\MessageValidator;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class VerifySnsSignature
{
    public function execute(Request $request): void
    {
        if (! config('aws-ses-observer.sns_signature_verification', true)) {
            return;
        }

        if (in_array(app()->environment(), ['local', 'testing'], true)) {
            return;
        }

        try {
            $message = new SnsMessage($request->all());
            $validator = new MessageValidator;

            if (! $validator->isValid($message)) {
                throw new HttpException(403, 'Invalid SNS signature.');
            }
        } catch (HttpException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new HttpException(403, 'SNS signature verification failed: '.$e->getMessage());
        }
    }
}
