<?php

declare(strict_types=1);

/**
 * Classify unread entries that do not already have a tag with the configured prefix.
 * This task is independent of the insertion-time enable_tags setting.
 */
function llmClassificationAssignUnreadTags(LlmClassificationExtension $extension): void {
        $prefix = $extension->getUserConfigurationString('tag_prefix') ?? '';
        Minz_Log::notice('LlmClassification: Starting unread-entry background task (prefix: ' . ($prefix !== '' ? $prefix : '<empty>') . ')');
        $lockPath = DATA_PATH . '/llm_classification_unread.lock';
        $lockHandle = @fopen($lockPath, 'c');
        if ($lockHandle === false || !flock($lockHandle, LOCK_EX | LOCK_NB)) {
                if (is_resource($lockHandle)) {
                        fclose($lockHandle);
                }
                Minz_Log::notice('LlmClassification: Unread-entry background task skipped because another run is still active');
                return;
        }

        try {
                $entryDAO = FreshRSS_Factory::createEntryDao();
                $entries = iterator_to_array($entryDAO->listWhere(
                        'a',
                        0,
                        FreshRSS_Entry::STATE_NOT_READ,
                        limit: -1, // Inspect all unread entries; the loop processes only 10 eligible entries.
                ), false);
                $unreadCount = 0;
                $skippedCount = 0;
                $processedCount = 0;
                $failedCount = 0;
                $eligibleCount = 0;
                unset($entryDAO);
                foreach ($entries as $entry) {
                        $unreadCount++;
                        if ($eligibleCount >= 10) {
                                break;
                        }
                        $hasPrefixTag = false;
                        if ($prefix !== '') {
                                foreach ($entry->tags() as $tag) {
                                        if (str_starts_with($tag, $prefix)) {
                                                $hasPrefixTag = true;
                                                break;
                                        }
                                }
                        }
                        if ($hasPrefixTag) {
                                $skippedCount++;
                                Minz_Log::notice('LlmClassification: Skipping unread entry ' . $entry->id() . ' because it already has the configured prefix');
                                continue;
                        }
                        try {
                                $title = trim(preg_replace('/\s+/', ' ', strip_tags($entry->title())) ?? '');
                                $title = mb_strlen($title) > 120 ? mb_substr($title, 0, 117) . '...' : $title;
                                $entryLabel = 'entry ' . $entry->id() . ($title !== '' ? ' "' . $title . '"' : '');
                                Minz_Log::notice('LlmClassification: Classifying ' . $entryLabel);
                                $eligibleCount++;
                                $classifiedEntry = $extension->classifyEntry($entry, backgroundTask: true);
                                try {
                                        $entryDAO = FreshRSS_Factory::createEntryDao();
                                        $entryDAO->updateEntry($classifiedEntry->toArray());
                                } finally {
                                        unset($entryDAO);
                                }
                                $processedCount++;
                                Minz_Log::notice('LlmClassification: Classified ' . $entryLabel);
                        } catch (Throwable $e) {
                                $failedCount++;
                                Minz_Log::warning('LlmClassification: Failed to classify ' . ($entryLabel ?? ('entry ' . $entry->id())) . ': ' . $e->getMessage());
                        }
                }
                Minz_Log::notice('LlmClassification: Unread-entry background task finished; unread=' . $unreadCount . ', processed=' . $processedCount . ', skipped=' . $skippedCount . ', failed=' . $failedCount);
        } finally {
                flock($lockHandle, LOCK_UN);
                fclose($lockHandle);
        }
}
