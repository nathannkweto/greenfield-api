<?php declare(strict_types=1);

namespace App\Services\Api;

use App\Models\AcademicEvent;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use DateTime;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use OpenAPI\Server\Api\TermsApiInterface;
use OpenAPI\Server\Model\AcademicEventRequest;
use OpenAPI\Server\Model\AcademicEventResponse;
use OpenAPI\Server\Model\AcademicEventResponseData;
use OpenAPI\Server\Model\AcademicTermRequest;
use OpenAPI\Server\Model\AcademicTermResponse;
use OpenAPI\Server\Model\AcademicTermResponseData;
use OpenAPI\Server\Model\AcademicYearRequest;
use OpenAPI\Server\Model\AcademicYearResponse;
use OpenAPI\Server\Model\AcademicYearResponseData;
use OpenAPI\Server\Model\ErrorResponse;
use OpenAPI\Server\Model\ValidationErrorResponse;
use Throwable;

class TermApiService implements TermsApiInterface
{
    /**
     * Create Academic Year
     */
    public function academicYearsCreatePost(
        AcademicYearRequest $AcademicYearRequest,
    ): AcademicYearResponse|ErrorResponse|ValidationErrorResponse {
        try {
            /** @var AcademicYear $yearRecord */
            $yearRecord = AcademicYear::query()->create([
                'year' => $AcademicYearRequest->year,
                'start_date' => $AcademicYearRequest->start_date->format('Y-m-d'),
                'end_date' => $AcademicYearRequest->end_date->format('Y-m-d'),
            ]);

            $responseData = new AcademicYearResponseData(
                id: (int) $yearRecord->id,
                year: (int) $yearRecord->year,
                start_date: new DateTime($yearRecord->start_date->toDateString()),
                end_date: new DateTime($yearRecord->end_date->toDateString()),
                created_at: new DateTime($yearRecord->created_at->toDateTimeString()),
                updated_at: new DateTime($yearRecord->updated_at->toDateTimeString()),
            );

            return new AcademicYearResponse(
                status: 'success',
                message: 'Academic year created successfully.',
                data: $responseData,
            );
        } catch (Throwable $e) {
            return new ErrorResponse(
                message: 'Failed to create academic year.',
                error: $e->getMessage(),
            );
        }
    }

    /**
     * Create Academic Event
     */
    public function academicYearsIdEventsCreatePost(
        int $id,
        AcademicEventRequest $AcademicEventRequest,
    ): AcademicEventResponse|ErrorResponse|ValidationErrorResponse {
        try {
            /** @var AcademicYear $academicYear */
            $academicYear = AcademicYear::query()->findOrFail($id);

            /** @var AcademicEvent $eventRecord */
            $eventRecord = AcademicEvent::query()->create([
                'academic_year_id' => $academicYear->id,
                'title' => $AcademicEventRequest->title,
                'start_date' => $AcademicEventRequest->start_date->format('Y-m-d'),
                'end_date' => $AcademicEventRequest->end_date->format('Y-m-d'),
            ]);

            $responseData = new AcademicEventResponseData(
                id: (int) $eventRecord->id,
                academic_year_id: (int) $eventRecord->academic_year_id,
                title: $eventRecord->title,
                start_date: new DateTime($eventRecord->start_date->toDateString()),
                end_date: new DateTime($eventRecord->end_date->toDateString()),
                created_at: new DateTime($eventRecord->created_at->toDateTimeString()),
                updated_at: new DateTime($eventRecord->updated_at->toDateTimeString()),
            );

            return new AcademicEventResponse(
                status: 'success',
                message: 'Academic event created successfully.',
                data: $responseData,
            );
        } catch (ModelNotFoundException) {
            return new ErrorResponse(
                message: 'Resource not found.',
                error: "Academic Year with ID $id was not found.",
            );
        } catch (Throwable $e) {
            return new ErrorResponse(
                message: 'Failed to create academic event.',
                error: $e->getMessage(),
            );
        }
    }

    /**
     * Create Academic Term
     */
    public function academicYearsIdTermsCreatePost(
        int $id,
        AcademicTermRequest $AcademicTermRequest,
    ): AcademicTermResponse|ErrorResponse|ValidationErrorResponse {
        try {
            /** @var AcademicYear $academicYear */
            $academicYear = AcademicYear::query()->findOrFail($id);

            /** @var AcademicTerm $termRecord */
            $termRecord = AcademicTerm::query()->create([
                'academic_year_id' => $academicYear->id,
                'term' => $AcademicTermRequest->term->value,
                'start_date' => $AcademicTermRequest->start_date->format('Y-m-d'),
                'end_date' => $AcademicTermRequest->end_date->format('Y-m-d'),
            ]);

            $responseData = new AcademicTermResponseData(
                id: (int) $termRecord->id,
                academic_year_id: (int) $termRecord->academic_year_id,
                term: $termRecord->term,
                start_date: new DateTime($termRecord->start_date->toDateString()),
                end_date: new DateTime($termRecord->end_date->toDateString()),
                created_at: new DateTime($termRecord->created_at->toDateTimeString()),
                updated_at: new DateTime($termRecord->updated_at->toDateTimeString()),
            );

            return new AcademicTermResponse(
                status: 'success',
                message: 'Academic term created successfully.',
                data: $responseData,
            );
        } catch (ModelNotFoundException) {
            return new ErrorResponse(
                message: 'Resource not found.',
                error: "Academic Year with ID $id was not found.",
            );
        } catch (Throwable $e) {
            return new ErrorResponse(
                message: 'Failed to create academic term.',
                error: $e->getMessage(),
            );
        }
    }
}
