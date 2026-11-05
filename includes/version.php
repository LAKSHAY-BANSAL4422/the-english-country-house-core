<?php
/**
 * Application version. Bumped with every release — when the files on the server are newer than the version
 * recorded in the database, the app upgrades the database, clears every cache automatically and tells the
 * admin on the dashboard. (Lives outside config.php so that it is updated together with the code.)
 */
define('APP_VERSION', '5.4.0');
define('APP_RELEASE_DATE', '2026-10-31');
