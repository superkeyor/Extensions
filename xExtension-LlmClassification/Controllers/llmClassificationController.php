<?php

declare(strict_types=1);

final class FreshExtension_llmClassification_Controller extends FreshRSS_ActionController {

	#[\Override]
	public function firstAction(): void {
		$this->view->_layout(null);
	}

	/**
	 * JSON unread counts per category and per feed, broken down by prefixed LLM tag.
	 * GET ./?c=llmClassification&a=counts
	 */
	public function countsAction(): void {
		if (!FreshRSS_Auth::hasAccess()) {
			Minz_Error::error(403);
			return;
		}

		$prefix = Minz_ExtensionManager::findExtension('LLM Classification')?->getUserConfigurationString('tag_prefix') ?? '';
		$prefixLength = strlen($prefix);

		// Identical tag strings collapse into one row, so this stays small even with many unread entries
		$sql = <<<'SQL'
			SELECT e.id_feed, f.category, f.priority, e.tags, COUNT(*) AS nb
			FROM `_entry` e
			INNER JOIN `_feed` f ON f.id = e.id_feed
			WHERE e.is_read = 0
			GROUP BY e.id_feed, f.category, f.priority, e.tags
			SQL;
		$rows = FreshRSS_Factory::createEntryDao()->fetchAssoc($sql) ?? [];

		$categories = [];
		$feeds = [];
		foreach ($rows as $row) {
			$nb = (int)$row['nb'];
			$tags = [];
			if ($prefix !== '') {
				// Same splitting as FreshRSS_Entry::_tags()
				foreach (preg_split('/\s*[#,]\s*/', (string)($row['tags'] ?? ''), -1, PREG_SPLIT_NO_EMPTY) ?: [] as $tag) {
					if (str_starts_with($tag, $prefix)) {
						$tags[] = htmlspecialchars_decode(substr($tag, $prefixLength), ENT_QUOTES);
					}
				}
				$tags = array_unique($tags);
			}
			self::addCounts($feeds[(int)$row['id_feed']], $nb, $tags);
			// Category views exclude feeds muted from their category
			if ((int)$row['priority'] >= FreshRSS_Feed::PRIORITY_CATEGORY) {
				self::addCounts($categories[(int)$row['category']], $nb, $tags);
			}
		}

		header('Content-Type: application/json; charset=UTF-8');
		header('Cache-Control: private, no-store');
		$this->view->content = json_encode([
			'prefix' => $prefix,
			'categories' => (object)$categories,
			'feeds' => (object)$feeds,
		], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE) ?: '{}';
	}

	/**
	 * @param array{unread:int,tags:array<string,int>}|null $group
	 * @param array<string> $tags
	 */
	private static function addCounts(?array &$group, int $nb, array $tags): void {
		$group ??= ['unread' => 0, 'tags' => []];
		$group['unread'] += $nb;
		foreach ($tags as $tag) {
			$group['tags'][$tag] = ($group['tags'][$tag] ?? 0) + $nb;
		}
	}
}
