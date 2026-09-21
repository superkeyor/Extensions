<?php

return array(
	'llm_classification' => array(
		'api' => array(
			'title' => 'API Configuration',
			'url' => 'API URL',
			'url_help' => 'OpenAI-compatible API base URL (e.g. <code>https://api.openai.com/v1</code>)',
			'key' => 'API Key',
			'key_help' => 'Bearer token for authentication',
			'model' => 'Model',
			'model_help' => 'Model name (e.g. gpt-4o-mini)',
			'timeout' => 'Timeout (seconds)',
			'max_tokens' => 'Max completion tokens',
			'max_tokens_help' => 'Maximum number of tokens the LLM may generate (0 = provider default)',
			'max_retries' => 'Max retries',
			'max_retries_help' => 'Number of retry attempts on transient errors (timeouts, invalid response, 500). 0 = no retry.',
			'allow_thinking' => 'Allow thinking mode<br />(if available)',
			'allow_thinking_help' => 'Allow thinking mode (<i>reasoning</i>) for models that support it. Uncheck to disable, e.g. to make output length shorter.',
                        'background_task' => 'Run unread-article tagging in the background',
                        'background_task_help' => 'During FreshRSS maintenance, process unread entries without the configured prefix. This is independent of feed-refresh tagging; enable either option as needed. Overlapping runs are skipped.',
                        'background_task_batch_size' => 'Articles per background run',
                        'background_task_batch_size_help' => 'Maximum number of eligible unread articles to process each time the background task runs (default: 20).',
		),
		'prompts' => array(
			'title' => 'Prompts',
			'system' => 'System prompt (read-only)',
			'user' => 'User prompt',
			'user_help' => 'Available placeholders:  <code>{title}</code>, <code>{content}</code>, <code>{author}</code>, <code>{url}</code>, <code>{feed_url}</code>, <code>{feed_name}</code>, <code>{date}</code>, <code>{tags}</code>',
			'max_content_length' => 'Max content length (characters)',
			'max_content_length_help' => 'Maximum number of characters for the {content} placeholder (0 = unlimited)',
		),
		'tags' => array(
			'title' => 'Tag Classification',
			'enable' => 'Enable tag classification',
                        'enable_help' => 'Controls tagging during feed refresh when enabled. Leave this unchecked to avoid LLM calls while feeds are being written; background tagging is controlled separately.',
			'prefix' => 'Tag prefix',
			'prefix_help' => 'Prefix prepended to each tag from the LLM (e.g. "llm/"). During background processing, an unread article is skipped only if it already has a tag starting with this prefix; other tags do not prevent tagging.',
			'allowed' => 'Allowed tags (one per line)',
			'allowed_help' => 'If set, only these tags will be accepted from the LLM response. Empty = all tags allowed.',
		),
		'filter' => array(
			'title' => 'Conditions for tagging',
			'search' => 'Search filters',
			'search_help' => 'Only classify entries matching at least one of these filters. Leave empty to classify all entries.',
		),
		'default_prompt' => 'Classify the following article.

Title: {title}
Author: {author}
Date: {date}
URL: {url}
Feed: {feed_name} ({feed_url})
Existing tags: {tags}

Content:
{content}',
	),
);
