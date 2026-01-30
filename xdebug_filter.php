<?php
$src = __DIR__ . DIRECTORY_SEPARATOR . "TNotifyer" . DIRECTORY_SEPARATOR;

xdebug_set_filter(
	XDEBUG_FILTER_CODE_COVERAGE,
	XDEBUG_PATH_INCLUDE,
	[ $src ]
);

print("Xdebug set filter: $src\n");
