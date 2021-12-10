<?php
/**
 * RocketGeek RocketGeek_Akismet_API Library
 *
 * @package    RocketGeek_Akismet_API
 * @version    1.1.0
 *
 * @link       https://akismet.com/development/api/
 * @link       https://github.com/rocketgeek/akismet_api/
 * @author     Chad Butler <https://butlerblog.com>
 * @author     RocketGeek <https://rocketgeek.com>
 * @copyright  Copyright (c) 2019-2021 Chad Butler
 * @license    Apache-2.0
 *
 * Copyright [2021] Chad Butler, RocketGeek
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * 
 *     https://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}
global $rktgk_akismet;
include_once( 'rocketgeek-akismet-api.php' );
$rktgk_akismet = new RocketGeek_Akismet_API();