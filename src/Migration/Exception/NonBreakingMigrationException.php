<?php

namespace GFExcel\Migration\Exception;

/**
 * Exception that represents that something went wrong, but it should not break the migration (or process).
 * @since 2.0.1
 */
final class NonBreakingMigrationException extends MigrationException {
}
