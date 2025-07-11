<?php

namespace GFExcel\Repository;

interface FormRepositoryInterface
{
    /**
     * Should retrieve every entry for a form id.
     *
     * Method should return an iterable, be it a generator, Iterator or a flat array. The resulting items should be
     * the default entry array Gravity Forms returns.
     *
     * @since 2.4.0
     * @param int $form_id The form id to retrieve the entries from.
     * @param array $search_criteria (Optional) search criteria.
     * @param string [] $sorting (Optional) sorting criteria.
     * @return iterable All entries for a form.
     */
    public function getEntries(int $form_id, array $search_criteria = [], array $sorting = []): iterable;

    /**
     * Should return the download url of the form.
     * @since 2.4.0
     * @param array $settings The settings for a form.
     * @return string|null The url, or null if not available.
     */
    public function getDownloadUrl(array $settings): ?string;
}
