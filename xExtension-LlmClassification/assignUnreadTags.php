<?php

declare(strict_types=1);

function llmClassificationAssignUnreadTags(LlmClassificationExtension $extension): void {
        $lockPath = DATA_PATH . '/llm_classification_unread.lock';
        $lockHandle = @fopen($lockPath, 'c');
        if ($lockHandle === false || !flock($lockHandle, LOCK_EX | LOCK_NB)) {
                if (is_resource($lockHandle)) {
                        fclose($lockHandle);
                }
                Minz_Log::debug('LlmClassification: Unread-entry task already running');
                return;
        }

        try {
                $entryDAO = FreshRSS_Factory::createEntryDao();
                $entries = $entryDAO->listWhere('a', 0, FreshRSS_Entry::STATE_NOT_READ);
                foreach ($entries as $entry) {
                        if ($entry->tags() !== []) {
                                continue;
                        }
                        try {
                                $classifiedEntry = $extension->classifyEntry($entry);
                                $entryDAO->updateEntry($classifiedEntry->toArray());
                        } catch (Throwable $e) {
                                Minz_Log::warning('LlmClassification: Failed to classify entry: ' . $e->getMessage());
                        }
                }
        } finally {
                flock($lockHandle, LOCK_UN);
                fclose($lockHandle);
        }
}
