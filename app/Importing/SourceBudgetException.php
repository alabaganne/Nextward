<?php

namespace App\Importing;

// Thrown when a run hits its request or time budget; the work resumes in the next batch.
class SourceBudgetException extends SourceRequestException
{
}
