<?php

namespace Webkul\MasterDataManagement\Enums;

/**
 * The stable `code` a master data REST response carries for clients to branch on. `pending_approval` marks a 202
 * for a write held for approval; `possible_duplicate` is reserved and not emitted yet.
 */
enum ApiErrorCode: string
{
    case EntityTypeNotFound = 'entity_type_not_found';
    case RecordNotFound = 'record_not_found';
    case XrefNotFound = 'xref_not_found';
    case CodeListNotFound = 'code_list_not_found';
    case HierarchyNotFound = 'hierarchy_not_found';
    case ForbiddenEntityType = 'forbidden_entity_type';
    case FilterNotSearchable = 'filter_not_searchable';
    case StaleRevision = 'stale_revision';
    case PreconditionRequired = 'precondition_required';
    case MergedRecord = 'merged_record';
    case PossibleDuplicate = 'possible_duplicate';
    case IdempotencyInFlight = 'idempotency_in_flight';
    case IdempotencyMismatch = 'idempotency_mismatch';
    case PendingApproval = 'pending_approval';
    case AlreadyPending = 'already_pending';
}
