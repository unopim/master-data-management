<?php

namespace Webkul\MasterDataManagement\Quality\Rules;

/**
 * A quality rule that compares a record with the other records of its type, such as duplicate detection.
 *
 * Its findings on write come from the queued match on write instead of the synchronous check, so a save never waits
 * for the candidate lookup and scoring.
 */
interface ComparesRecords extends QualityRule {}
