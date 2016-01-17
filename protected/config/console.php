<?php
date_default_timezone_set('UTC');

return [
	'basePath' => BASEPATH . '/protected/',

	'components' => [
    'messageMedia' => [
			'class'    => 'MessageMedia',
			'username' => MM_USER,
			'password' => MM_PASS,
		],
  ],
];