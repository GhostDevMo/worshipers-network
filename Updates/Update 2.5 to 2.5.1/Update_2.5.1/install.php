<?php
/**
 * Sngine installer
 * 
 * @package Sngine
 * @author Zamblek
 */

// set system version
define('SYS_VER', '2.5.1');

// set absolut & base path
define('ABSPATH',dirname(__FILE__).'/');
define('BASEPATH',dirname($_SERVER['PHP_SELF']));


// check the config file
if(file_exists(ABSPATH.'includes/config.php')) {
    /* the config file exist -> start the system */
    header('Location: ./');
}


// enviroment settings
error_reporting(E_ALL ^ (E_NOTICE | E_WARNING));


// get functions
require(ABSPATH.'includes/functions.php');


// check system requirements
check_system_requirements();


// install
if(isset($_POST['submit'])) {

    // [0] check valid purchase code
    /* get licence key */
    try {
        $licence_key = get_licence_key($_POST['purchase_code']);
        if(is_empty($_POST['purchase_code']) || $licence_key === false) {
            _error("Error", "Please enter a valid purchase code");
        }
        /* update session hash for AJAX CSRF security */
        $session_hash = $licence_key;
    } catch (Exception $e) {
        _error("Error", $e->getMessage());
    }
    
    
    // [1] connect to the db
    $db = new mysqli($_POST['db_host'], $_POST['db_username'], $_POST['db_password'], $_POST['db_name']);
    if(mysqli_connect_error()) {
        _error(DB_ERROR);
    }


    // [2] check admin data
    /* check email */
    if(!valid_email($_POST['admin_email'])) {
        _error("Error", "Please enter a valid email address");
    }
    /* check username */
    if(!valid_username($_POST['admin_username'])) {
        _error("Error", "Please enter a valid username (a-z0-9_.) with minimum 3 characters long");
    }
    if(reserved_username($_POST['admin_username'])) {
        _error("Error", "You can't use"." <strong>".$_POST['admin_username']."</strong> "."as username");
    }
    /* check password */
    if(is_empty($_POST['admin_password']) || strlen($_POST['admin_password']) < 6) {
        _error("Error", "Your password must be at least 6 characters long. Please try another");
    }


    // [3] create the database
    $structure = "

-- --------------------------------------------------------

--
-- Table structure for table `ads`
--

CREATE TABLE `ads` (
  `ads_id` int(10) NOT NULL,
  `title` varchar(255) NOT NULL,
  `place` varchar(32) NOT NULL,
  `code` text NOT NULL,
  `time` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `affiliates_payments`
--

CREATE TABLE `affiliates_payments` (
  `payment_id` int(10) NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `email` varchar(64) NOT NULL,
  `amount` varchar(32) NOT NULL,
  `method` varchar(64) NOT NULL,
  `time` datetime NOT NULL,
  `status` tinyint(1) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `announcement_id` int(10) NOT NULL,
  `name` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `type` varchar(32) NOT NULL,
  `code` text NOT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;

-- --------------------------------------------------------

--
-- Table structure for table `announcements_users`
--

CREATE TABLE `announcements_users` (
  `announcement_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `banned_ips`
--

CREATE TABLE `banned_ips` (
  `ip_id` int(10) UNSIGNED NOT NULL,
  `ip` varchar(64) NOT NULL,
  `time` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `conversations`
--

CREATE TABLE `conversations` (
  `conversation_id` int(10) UNSIGNED NOT NULL,
  `last_message_id` int(10) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `conversations_messages`
--

CREATE TABLE `conversations_messages` (
  `message_id` int(10) UNSIGNED NOT NULL,
  `conversation_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `message` longtext NOT NULL,
  `image` varchar(255) NOT NULL,
  `time` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `conversations_users`
--

CREATE TABLE `conversations_users` (
  `conversation_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `seen` enum('0','1') NOT NULL DEFAULT '0',
  `deleted` enum('0','1') NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=FIXED;

-- --------------------------------------------------------

--
-- Table structure for table `custom_fields`
--

CREATE TABLE `custom_fields` (
  `field_id` int(10) UNSIGNED NOT NULL,
  `type` varchar(32) NOT NULL,
  `select_options` text NOT NULL,
  `label` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `place` varchar(32) NOT NULL,
  `length` int(10) NOT NULL DEFAULT '32',
  `mandatory` enum('0','1') NOT NULL DEFAULT '0',
  `in_registration` enum('0','1') NOT NULL DEFAULT '0',
  `in_profile` enum('0','1') NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `emojis`
--

CREATE TABLE `emojis` (
  `emoji_id` int(10) UNSIGNED NOT NULL,
  `pattern` varchar(255) NOT NULL,
  `class` varchar(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

--
-- Dumping data for table `emojis`
--

INSERT INTO `emojis` (`emoji_id`, `pattern`, `class`) VALUES
(1, ':)', 'smile'),
(2, '(&lt;', 'joy'),
(3, ':D', 'smiley'),
(4, ':(', 'worried'),
(5, ':relaxed:', 'relaxed'),
(6, ':P', 'stuck-out-tongue'),
(7, ':O', 'open-mouth'),
(8, ':/', 'confused'),
(9, ';)', 'wink'),
(10, ';(', 'sob'),
(11, 'B|', 'sunglasses'),
(12, ':disappointed:', 'disappointed'),
(13, ':yum:', 'yum'),
(14, '^_^', 'grin'),
(15, ':no_mouth:', 'no-mouth'),
(16, '*_*', 'heart-eyes'),
(17, '*)', 'kissing-heart'),
(18, 'O:)', 'innocent'),
(19, ':angry:', 'angry'),
(20, ':rage:', 'rage'),
(21, ':smirk:', 'smirk'),
(22, ':flushed:', 'flushed'),
(23, ':satisfied:', 'satisfied'),
(24, ':relieved:', 'relieved'),
(25, ':sleeping:', 'sleeping'),
(26, ':stuck_out_tongue:', 'stuck-out-tongue'),
(27, ':stuck_out_tongue_closed_eyes:', 'stuck-out-tongue-closed-eyes'),
(28, ':frowning:', 'frowning'),
(29, ':anguished:', 'anguished'),
(30, ':open_mouth:', 'open-mouth'),
(31, ':grimacing:', 'grimacing'),
(32, ':hushed:', 'hushed'),
(33, ':expressionless:', 'expressionless'),
(34, ':unamused:', 'unamused'),
(35, ':sweat_smile:', 'sweat-smile'),
(36, ':sweat:', 'sweat'),
(37, ':confounded:', 'confounded'),
(38, ':weary:', 'weary'),
(39, ':pensive:', 'pensive'),
(40, ':fearful:', 'fearful'),
(41, ':cold_sweat:', 'cold-sweat'),
(42, ':persevere:', 'persevere'),
(43, ':cry:', 'cry'),
(44, ':astonished:', 'astonished'),
(45, ':scream:', 'scream'),
(46, ':mask:', 'mask'),
(47, ':tired_face:', 'tired-face'),
(48, ':triumph:', 'triumph'),
(49, ':dizzy_face:', 'dizzy-face'),
(50, ':imp:', 'imp'),
(51, ':smiling_imp:', 'smiling-imp'),
(52, ':neutral_face:', 'neutral-face'),
(53, ':alien:', 'alien'),
(54, ':yellow_heart:', 'yellow-heart'),
(55, ':blue_heart:', 'blue-heart'),
(56, ':blue_heart:', 'blue-heart'),
(57, ':heart:', 'heart'),
(58, ':green_heart:', 'green-heart'),
(59, ':broken_heart:', 'broken-heart'),
(60, ':heartbeat:', 'heartbeat'),
(61, ':heartpulse:', 'heartpulse'),
(62, ':two_hearts:', 'two-hearts'),
(63, ':revolving_hearts:', 'revolving-hearts'),
(64, ':cupid:', 'cupid'),
(65, ':sparkling_heart:', 'sparkling-heart'),
(66, ':sparkles:', 'sparkles'),
(67, ':star:', 'star'),
(68, ':star2:', 'star2'),
(69, ':dizzy:', 'dizzy'),
(70, ':boom:', 'boom'),
(71, ':exclamation:', 'exclamation'),
(72, ':anger:', 'anger'),
(73, ':question:', 'question'),
(74, ':grey_exclamation:', 'grey-exclamation'),
(75, ':grey_question:', 'grey-question'),
(76, ':zzz:', 'zzz'),
(77, ':dash:', 'dash'),
(78, ':sweat_drops:', 'sweat-drops'),
(79, ':notes:', 'notes'),
(80, ':musical_note:', 'musical-note'),
(81, ':fire:', 'fire'),
(82, ':poop:', 'poop'),
(83, ':thumbsup:', 'thumbsup'),
(84, ':thumbsdown:', 'thumbsdown'),
(85, ':ok_hand:', 'ok-hand'),
(86, ':punch:', 'punch'),
(87, ':fist:', 'fist'),
(88, ':v:', 'v'),
(89, ':wave:', 'wave'),
(90, ':hand:', 'hand'),
(91, ':raised_hand:', 'raised-hand'),
(92, ':open_hands:', 'open-hands'),
(93, ':point_up:', 'point-up'),
(94, ':point_down:', 'point-down'),
(95, ':point_left:', 'point-left'),
(96, ':point_right:', 'point-right'),
(97, ':raised_hands:', 'raised-hands'),
(98, ':pray:', 'pray'),
(99, ':clap:', 'clap'),
(100, ':muscle:', 'muscle'),
(101, ':runner:', 'runner'),
(102, ':couple:', 'couple'),
(103, ':family:', 'family'),
(104, ':two_men_holding_hands:', 'two-men-holding-hands'),
(105, ':two_women_holding_hands:', 'two-women-holding-hands'),
(106, ':dancer:', 'dancer'),
(107, ':dancers:', 'dancers'),
(108, ':ok_woman:', 'ok-woman'),
(109, ':no_good:', 'no-good'),
(110, ':information_desk_person:', 'information-desk-person'),
(111, ':bride_with_veil:', 'bride-with-veil'),
(112, ':couplekiss:', 'couplekiss'),
(113, ':couple_with_heart:', 'couple-with-heart'),
(114, ':nail_care:', 'nail-care'),
(115, ':boy:', 'boy'),
(116, ':girl:', 'girl'),
(117, ':woman:', 'woman'),
(118, ':man:', 'man'),
(119, ':baby:', 'baby'),
(120, ':older_woman:', 'older-woman'),
(121, ':older_man:', 'older-man'),
(122, ':cop:', 'cop'),
(123, ':angel:', 'angel'),
(124, ':princess:', 'princess'),
(125, ':smiley_cat:', 'smiley-cat'),
(126, ':smile_cat:', 'smile-cat'),
(127, ':heart_eyes_cat:', 'heart-eyes-cat'),
(128, ':kissing_cat:', 'kissing-cat'),
(129, ':smirk_cat:', 'smirk-cat'),
(130, ':scream_cat:', 'scream-cat'),
(131, ':crying_cat_face:', 'crying-cat-face'),
(132, ':joy_cat:', 'joy-cat'),
(133, ':pouting_cat:', 'pouting-cat'),
(134, ':japanese_ogre:', 'japanese-ogre'),
(135, ':see_no_evil:', 'see-no-evil'),
(136, ':hear_no_evil:', 'hear-no-evil'),
(137, ':speak_no_evil:', 'speak-no-evil'),
(138, ':guardsman:', 'guardsman'),
(139, ':skull:', 'skull'),
(140, ':feet:', 'feet'),
(141, ':lips:', 'lips'),
(142, ':kiss:', 'kiss'),
(143, ':droplet:', 'droplet'),
(144, ':ear:', 'ear'),
(145, ':eyes:', 'eyes'),
(146, ':nose:', 'nose'),
(147, ':tongue:', 'tongue'),
(148, ':love_letter:', 'love-letter'),
(149, ':speech_balloon:', 'speech-balloon'),
(150, ':thought_balloon:', 'thought-balloon'),
(151, ':sunny:', 'sunny');

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `event_id` int(10) UNSIGNED NOT NULL,
  `event_privacy` enum('secret','closed','public') DEFAULT 'public',
  `event_admin` int(10) UNSIGNED NOT NULL,
  `event_category` int(10) UNSIGNED NOT NULL,
  `event_title` varchar(255) NOT NULL,
  `event_location` varchar(255) DEFAULT NULL,
  `event_description` text NOT NULL,
  `event_start_date` datetime NOT NULL,
  `event_end_date` datetime NOT NULL,
  `event_cover` varchar(255) DEFAULT NULL,
  `event_cover_id` int(10) UNSIGNED DEFAULT NULL,
  `event_album_covers` int(10) DEFAULT NULL,
  `event_album_timeline` int(10) DEFAULT NULL,
  `event_pinned_post` int(10) DEFAULT NULL,
  `event_invited` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `event_interested` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `event_going` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `event_date` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `events_categories`
--

CREATE TABLE `events_categories` (
  `category_id` int(10) UNSIGNED NOT NULL,
  `category_name` varchar(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

--
-- Dumping data for table `events_categories`
--

INSERT INTO `events_categories` (`category_id`, `category_name`) VALUES
(1, 'Art'),
(2, 'Causes'),
(3, 'Crafts'),
(4, 'Dance'),
(5, 'Drinks'),
(6, 'Film'),
(7, 'Fitness'),
(8, 'Food'),
(9, 'Games'),
(10, 'Gardening'),
(11, 'Health'),
(12, 'Home'),
(13, 'Literature'),
(14, 'Music'),
(15, 'Networking'),
(16, 'Other'),
(17, 'Party'),
(18, 'Religion'),
(19, 'Shopping'),
(20, 'Sports'),
(21, 'Theater'),
(22, 'Wellness');

-- --------------------------------------------------------

--
-- Table structure for table `events_members`
--

CREATE TABLE `events_members` (
  `event_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `is_invited` enum('0','1') DEFAULT '0',
  `is_interested` enum('0','1') DEFAULT '0',
  `is_going` enum('0','1') DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=FIXED;

-- --------------------------------------------------------

--
-- Table structure for table `followings`
--

CREATE TABLE `followings` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `following_id` int(10) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=FIXED;

-- --------------------------------------------------------

--
-- Table structure for table `friends`
--

CREATE TABLE `friends` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_one_id` int(10) UNSIGNED NOT NULL,
  `user_two_id` int(10) UNSIGNED NOT NULL,
  `status` tinyint(1) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `games`
--

CREATE TABLE `games` (
  `game_id` int(10) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `source` text NOT NULL,
  `thumbnail` varchar(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `game_players`
--

CREATE TABLE `game_players` (
  `game_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=FIXED;

-- --------------------------------------------------------

--
-- Table structure for table `groups`
--

CREATE TABLE `groups` (
  `group_id` int(10) UNSIGNED NOT NULL,
  `group_privacy` enum('secret','closed','public') DEFAULT 'public',
  `group_admin` int(10) UNSIGNED NOT NULL,
  `group_name` varchar(64) NOT NULL,
  `group_title` varchar(255) NOT NULL,
  `group_description` text NOT NULL,
  `group_picture` varchar(255) DEFAULT NULL,
  `group_picture_id` int(10) UNSIGNED DEFAULT NULL,
  `group_cover` varchar(255) DEFAULT NULL,
  `group_cover_id` int(10) UNSIGNED DEFAULT NULL,
  `group_album_pictures` int(10) DEFAULT NULL,
  `group_album_covers` int(10) DEFAULT NULL,
  `group_album_timeline` int(10) DEFAULT NULL,
  `group_pinned_post` int(10) DEFAULT NULL,
  `group_members` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `group_date` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `groups_members`
--

CREATE TABLE `groups_members` (
  `group_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `approved` enum('0','1') NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=FIXED;

-- --------------------------------------------------------

--
-- Table structure for table `market_categories`
--

CREATE TABLE `market_categories` (
  `category_id` int(10) UNSIGNED NOT NULL,
  `category_name` varchar(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

--
-- Dumping data for table `market_categories`
--

INSERT INTO `market_categories` (`category_id`, `category_name`) VALUES
(1, 'Apparel &amp; Accessories'),
(2, 'Autos &amp; Vehicles'),
(3, 'Baby &amp; Children&#039;s Products'),
(4, 'Beauty Products &amp; Services'),
(5, 'Computers &amp; Peripherals'),
(6, 'Consumer Electronics'),
(7, 'Dating Services'),
(8, 'Financial Services'),
(9, 'Gifts &amp; Occasions'),
(10, 'Home &amp; Garden'),
(11, 'Other');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(10) UNSIGNED NOT NULL,
  `to_user_id` int(10) UNSIGNED NOT NULL,
  `from_user_id` int(10) UNSIGNED NOT NULL,
  `action` varchar(32) NOT NULL,
  `node_type` varchar(32) NOT NULL,
  `node_url` varchar(255) NOT NULL,
  `notify_id` varchar(255) DEFAULT NULL,
  `time` datetime DEFAULT NULL,
  `seen` enum('0','1') NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `packages`
--

CREATE TABLE `packages` (
  `package_id` int(10) NOT NULL,
  `name` varchar(255) NOT NULL,
  `price` varchar(32) NOT NULL,
  `period_num` int(10) UNSIGNED NOT NULL,
  `period` varchar(32) NOT NULL,
  `color` varchar(32) NOT NULL,
  `icon` varchar(255) NOT NULL,
  `boost_posts_enabled` enum('0','1') NOT NULL DEFAULT '0',
  `boost_posts` int(10) UNSIGNED NOT NULL,
  `boost_pages_enabled` enum('0','1') NOT NULL DEFAULT '0',
  `boost_pages` int(10) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `packages_payments`
--

CREATE TABLE `packages_payments` (
  `payment_id` int(10) NOT NULL,
  `payment_date` datetime NOT NULL,
  `package_name` varchar(255) NOT NULL,
  `package_price` varchar(32) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `pages`
--

CREATE TABLE `pages` (
  `page_id` int(10) UNSIGNED NOT NULL,
  `page_admin` int(10) UNSIGNED NOT NULL,
  `page_category` int(10) UNSIGNED NOT NULL,
  `page_name` varchar(64) NOT NULL,
  `page_title` varchar(255) NOT NULL,
  `page_description` text NOT NULL,
  `page_picture` varchar(255) DEFAULT NULL,
  `page_picture_id` int(10) UNSIGNED DEFAULT NULL,
  `page_cover` varchar(255) DEFAULT NULL,
  `page_cover_id` int(10) UNSIGNED DEFAULT NULL,
  `page_album_pictures` int(10) UNSIGNED DEFAULT NULL,
  `page_album_covers` int(10) UNSIGNED DEFAULT NULL,
  `page_album_timeline` int(10) UNSIGNED DEFAULT NULL,
  `page_pinned_post` int(10) UNSIGNED DEFAULT NULL,
  `page_verified` enum('0','1') NOT NULL DEFAULT '0',
  `page_boosted` enum('0','1') NOT NULL DEFAULT '0',
  `page_likes` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `page_date` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `pages_categories`
--

CREATE TABLE `pages_categories` (
  `category_id` int(10) UNSIGNED NOT NULL,
  `category_name` varchar(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Dumping data for table `pages_categories`
--

INSERT INTO `pages_categories` (`category_id`, `category_name`) VALUES
(1, 'Service'),
(2, 'Musician/Band'),
(3, 'Brand or Product'),
(4, 'Company, Organization or Institution'),
(5, 'Artist, Public figure'),
(6, 'Entertainment'),
(7, 'Cause or Community');

-- --------------------------------------------------------

--
-- Table structure for table `pages_invites`
--

CREATE TABLE `pages_invites` (
  `page_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `from_user_id` int(10) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=FIXED;

-- --------------------------------------------------------

--
-- Table structure for table `pages_likes`
--

CREATE TABLE `pages_likes` (
  `page_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=FIXED;

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

CREATE TABLE `posts` (
  `post_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `user_type` enum('user','page') NOT NULL,
  `in_group` enum('0','1') NOT NULL DEFAULT '0',
  `group_id` int(10) UNSIGNED DEFAULT NULL,
  `in_event` enum('0','1') NOT NULL DEFAULT '0',
  `event_id` int(10) UNSIGNED DEFAULT NULL,
  `in_wall` enum('0','1') NOT NULL DEFAULT '0',
  `wall_id` int(10) UNSIGNED DEFAULT NULL,
  `post_type` varchar(32) NOT NULL,
  `origin_id` int(10) UNSIGNED DEFAULT NULL,
  `time` datetime NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `privacy` varchar(32) NOT NULL,
  `text` longtext,
  `feeling_action` varchar(32) DEFAULT NULL,
  `feeling_value` varchar(255) DEFAULT NULL,
  `boosted` enum('0','1') NOT NULL DEFAULT '0',
  `likes` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `comments` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `shares` int(10) UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `posts_articles`
--

CREATE TABLE `posts_articles` (
  `article_id` int(10) UNSIGNED NOT NULL,
  `post_id` int(10) UNSIGNED NOT NULL,
  `cover` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `text` longtext NOT NULL,
  `tags` text NOT NULL,
  `views` int(10) UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `posts_audios`
--

CREATE TABLE `posts_audios` (
  `audio_id` int(10) UNSIGNED NOT NULL,
  `post_id` int(10) UNSIGNED NOT NULL,
  `source` varchar(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `posts_comments`
--

CREATE TABLE `posts_comments` (
  `comment_id` int(10) UNSIGNED NOT NULL,
  `node_id` int(10) UNSIGNED NOT NULL,
  `node_type` enum('post','photo','comment') NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `user_type` enum('user','page') NOT NULL,
  `text` longtext NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `time` datetime NOT NULL,
  `likes` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `replies` int(10) UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `posts_comments_likes`
--

CREATE TABLE `posts_comments_likes` (
  `comment_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;

-- --------------------------------------------------------

--
-- Table structure for table `posts_files`
--

CREATE TABLE `posts_files` (
  `file_id` int(10) UNSIGNED NOT NULL,
  `post_id` int(10) UNSIGNED NOT NULL,
  `source` varchar(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `posts_hidden`
--

CREATE TABLE `posts_hidden` (
  `post_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;

-- --------------------------------------------------------

--
-- Table structure for table `posts_likes`
--

CREATE TABLE `posts_likes` (
  `post_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `posts_links`
--

CREATE TABLE `posts_links` (
  `link_id` int(10) UNSIGNED NOT NULL,
  `post_id` int(10) UNSIGNED NOT NULL,
  `source_url` tinytext NOT NULL,
  `source_host` varchar(255) NOT NULL,
  `source_title` varchar(255) NOT NULL,
  `source_text` text NOT NULL,
  `source_thumbnail` varchar(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `posts_media`
--

CREATE TABLE `posts_media` (
  `media_id` int(10) UNSIGNED NOT NULL,
  `post_id` int(10) NOT NULL,
  `source_url` text NOT NULL,
  `source_provider` varchar(255) NOT NULL,
  `source_type` varchar(255) NOT NULL,
  `source_title` varchar(255) DEFAULT NULL,
  `source_text` text,
  `source_html` text
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `posts_photos`
--

CREATE TABLE `posts_photos` (
  `photo_id` int(10) UNSIGNED NOT NULL,
  `post_id` int(10) UNSIGNED NOT NULL,
  `album_id` int(10) UNSIGNED DEFAULT NULL,
  `source` varchar(255) NOT NULL,
  `likes` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `comments` int(10) UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `posts_photos_albums`
--

CREATE TABLE `posts_photos_albums` (
  `album_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `user_type` enum('user','page') NOT NULL,
  `in_group` enum('0','1') NOT NULL DEFAULT '0',
  `group_id` int(10) UNSIGNED DEFAULT NULL,
  `in_event` enum('0','1') NOT NULL DEFAULT '0',
  `event_id` int(10) UNSIGNED DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `privacy` enum('me','friends','public','custom') NOT NULL DEFAULT 'public'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `posts_photos_likes`
--

CREATE TABLE `posts_photos_likes` (
  `photo_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=FIXED;

-- --------------------------------------------------------

--
-- Table structure for table `posts_polls`
--

CREATE TABLE `posts_polls` (
  `poll_id` int(10) UNSIGNED NOT NULL,
  `post_id` int(10) UNSIGNED NOT NULL,
  `votes` int(10) UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `posts_polls_options`
--

CREATE TABLE `posts_polls_options` (
  `option_id` int(10) UNSIGNED NOT NULL,
  `poll_id` int(10) UNSIGNED NOT NULL,
  `text` varchar(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `posts_products`
--

CREATE TABLE `posts_products` (
  `product_id` int(10) UNSIGNED NOT NULL,
  `post_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `price` varchar(32) NOT NULL,
  `category_id` int(10) UNSIGNED NOT NULL,
  `location` varchar(255) NOT NULL,
  `available` enum('0','1') NOT NULL DEFAULT '1'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `posts_saved`
--

CREATE TABLE `posts_saved` (
  `post_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `time` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `posts_videos`
--

CREATE TABLE `posts_videos` (
  `video_id` int(10) UNSIGNED NOT NULL,
  `post_id` int(10) UNSIGNED NOT NULL,
  `source` varchar(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `report_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `node_id` int(10) UNSIGNED NOT NULL,
  `node_type` varchar(32) NOT NULL,
  `time` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `static_pages`
--

CREATE TABLE `static_pages` (
  `page_id` int(10) NOT NULL,
  `page_url` varchar(64) NOT NULL,
  `page_title` varchar(255) NOT NULL,
  `page_text` text NOT NULL,
  `in_footer` enum('0','1') NOT NULL DEFAULT '1'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Dumping data for table `static_pages`
--

INSERT INTO `static_pages` (`page_id`, `page_url`, `page_title`, `page_text`, `in_footer`) VALUES
(1, 'about', 'About', '&lt;p&gt;\r\n                Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.\r\n            &lt;/p&gt;\r\n            &lt;h3 class=&quot;text-info&quot;&gt;\r\n                Big Title\r\n            &lt;/h3&gt;\r\n            &lt;p&gt;\r\n                Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.\r\n            &lt;/p&gt;\r\n            &lt;h3 class=&quot;text-info&quot;&gt;\r\n                Big Title\r\n            &lt;/h3&gt;\r\n            &lt;p&gt;\r\n               Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.\r\n            &lt;/p&gt;', '1'),
(2, 'terms', 'Terms', '&lt;p&gt;\r\n    &lt;strong&gt;\r\n        We run this website and permits its use according to the following terms and conditions:\r\n    &lt;/strong&gt;\r\n&lt;/p&gt;\r\n\r\n&lt;h3 class=&quot;text-info&quot;&gt;\r\n    Basic Terms:\r\n&lt;/h3&gt;\r\n&lt;ol&gt;\r\n    &lt;li&gt;Using this website implies your acceptance of these conditions. If you do not fully accept them, your entry to this site will be considered unauthorized and you will have to stop using it immediately&lt;/li&gt;\r\n    &lt;li&gt;You must be 13 years or older to use this site.&lt;/li&gt;\r\n    &lt;li&gt;You are responsible for any activity that occurs under your screen name.&lt;/li&gt;\r\n    &lt;li&gt;You are responsible for keeping your account secure.&lt;/li&gt;\r\n    &lt;li&gt;You must not abuse, harass, threaten or intimidate other Sngine users.&lt;/li&gt;\r\n    &lt;li&gt;You are solely responsible for your conduct and any data, text, information, screen names, graphics, photos, profiles, audio and video clips, links (&quot;Content&quot;) that you submit, post, and display on the Sngine service.&lt;/li&gt;\r\n    &lt;li&gt;You must not modify, adapt or hack Sngine or modify another website so as to falsely imply that it is associated with Sngine&lt;/li&gt;\r\n    &lt;li&gt;You must not create or submit unwanted email to any Sngine members (&quot;Spam&quot;).&lt;/li&gt;\r\n    &lt;li&gt;You must not transmit any worms or viruses or any code of a destructive nature.&lt;/li&gt;\r\n    &lt;li&gt;You must not, in the use of Sngine, violate any laws in your jurisdiction (including but not limited to copyright laws).&lt;/li&gt;\r\n&lt;/ol&gt;\r\n&lt;p&gt;\r\n    Violation of any of these agreements will result in the termination of your Sngine account. While Sngine prohibits such conduct and content on its site, you understand and agree that Sngine cannot be responsible for the Content posted on its web site and you nonetheless may be exposed to such materials and that you use the Sngine service at your own risk.\r\n&lt;/p&gt;\r\n\r\n\r\n&lt;h3 class=&quot;text-info&quot;&gt;\r\n    General Conditions:\r\n&lt;/h3&gt;\r\n&lt;ol&gt;\r\n    &lt;li&gt;We reserve the right to modify or terminate the Sngine service for any reason, without notice at any time.&lt;/li&gt;\r\n    &lt;li&gt;We reserve the right to alter these Terms of Use at any time. If the alterations constitute a material change to the Terms of Use, we will notify you via internet mail according to the preference expressed on your account. What constitutes a &quot;material change&quot; will be determined at our sole discretion, in good faith and using common sense and reasonable judgement.&lt;/li&gt;\r\n    &lt;li&gt;We reserve the right to refuse service to anyone for any reason at any time.&lt;/li&gt;\r\n    &lt;li&gt;We may, but have no obligation to, remove Content and accounts containing Content that we determine in our sole discretion are unlawful, offensive, threatening, libelous, defamatory, obscene or otherwise objectionable or violates any party&#039;s intellectual property or these Terms of Use.&lt;/li&gt;\r\n    &lt;li&gt;Sngine service makes it possible to post images and text hosted on Sngine to outside websites. This use is accepted (and even encouraged!). However, pages on other websites which display data hosted on Sngine must provide a link back to Sngine.&lt;/li&gt;\r\n&lt;/ol&gt;\r\n\r\n&lt;h3 class=&quot;text-info&quot;&gt;\r\n    Copyright (What&#039;s Yours is Yours):\r\n&lt;/h3&gt;\r\n&lt;ol&gt;\r\n    &lt;li&gt;We claim no intellectual property rights over the material you provide to the Sngine service. Your profile and materials uploaded remain yours. You can remove your profile at any time by deleting your account. This will also remove any text and images you have stored in the system.&lt;/li&gt;\r\n    &lt;li&gt;We encourage users to contribute their creations to the public domain or consider progressive licensing terms.&lt;/li&gt;\r\n&lt;/ol&gt;\r\n&lt;small&gt;\r\n    &lt;i&gt;Last updated on: Jan 29, 2016&lt;/i&gt;\r\n&lt;/small&gt;', '1'),
(3, 'privacy', 'Privacy', '&lt;p&gt;\r\n    We recognize that your privacy is very important and take it seriously. This Privacy Policy describes Sngine&#039;s policies and procedures on the collection, use and disclosure of your information when you use the Sngine Service. We will not use or share your information with anyone except as described in this Privacy Policy.\r\n&lt;/p&gt;\r\n\r\n&lt;h3 class=&quot;text-info&quot;&gt;\r\n    Information Collection and Use\r\n&lt;/h3&gt;\r\n&lt;p&gt;\r\n    We uses information we collect to analyze how the Service is used, diagnose service or technical problems, maintain security, personalize content, remember information to help you efficiently access your account, monitor aggregate metrics such as total number of visitors, traffic, and demographic patterns, and track User Content and users as necessary to comply with the Digital Millennium Copyright Act and other applicable laws.\r\n&lt;/p&gt;\r\n&lt;h4 class=&quot;text-danger&quot;&gt;\r\n    User-Provided Information:\r\n&lt;/h5&gt;\r\n&lt;p&gt;\r\n    You provide us information about yourself, such as your name and e-mail address, if you register for a member account with the Service. Your name and other information you choose to add to your profile will be available for public viewing on the Service. We may use your email address to send you Service-related notices. You can control receipt of certain Service-related messages on your Settings page. We may also use your contact information to send you marketing messages. If you do not want to receive such messages, you may opt out by following the instructions in the message. If you correspond with us by email, we may retain the content of your email messages, your email address and our responses.\r\n&lt;/p&gt;\r\n&lt;p&gt;\r\n    You also provide us information in User Content you post to the Service. Your posts and other contributions on the Service, and metadata about them (such as when you posted them), are publicly viewable on the Service, along with your name (unless the Service permits you to post anonymously). This information may be searched by search engines and be republished elsewhere on the Web in accordance with our Terms of Service.\r\n&lt;/p&gt;\r\n&lt;p&gt;\r\n    If you choose to use our invitation service to invite a friend to the Service, we will ask you for that person&#039;s email address and automatically send an email invitation. We stores this information to send this email, to register your friend if your invitation is accepted, and to track the success of our invitation service.\r\n&lt;/p&gt;\r\n&lt;h4 class=&quot;text-danger&quot;&gt;\r\n    Cookies:\r\n&lt;/h5&gt;\r\n&lt;p&gt;\r\n    When you visit the Service, we may send one or more &quot;cookies&quot; - small data files - to your computer to uniquely identify your browser and let Sngine help you log in faster and enhance your navigation through the site. A cookie may convey anonymous information about how you browse the Service to us, but does not collect personal information about you. A persistent cookie remains on your computer after you close your browser so that it can be used by your browser on subsequent visits to the Service. Persistent cookies can be removed by following your web browser&#039;s directions. A session cookie is temporary and disappears after you close your browser. You can reset your web browser to refuse all cookies or to indicate when a cookie is being sent. However, some features of the Service may not function properly if the ability to accept cookies is disabled.\r\n&lt;/p&gt;\r\n&lt;h4 class=&quot;text-danger&quot;&gt;\r\n    Log Files:\r\n&lt;/h5&gt;\r\n&lt;p&gt;\r\n    Log file information is automatically reported by your browser each time you access a web page. When you use the Service, our servers automatically record certain information that your web browser sends whenever you visit any website. These server logs may include information such as your web request, Internet Protocol (&quot;IP&quot;) address, browser type, referring / exit pages and URLs, number of clicks, domain names, landing pages, pages viewed, and other such information.\r\n&lt;/p&gt;\r\n&lt;h4 class=&quot;text-danger&quot;&gt;\r\n    Third Party Services:\r\n&lt;/h5&gt;\r\n&lt;p&gt;\r\n    We may use Google Analytics or Mixpanel to help understand use of the Service. Google Analytics and Mixpanel collect the information sent by your browser as part of a web page request, including cookies and your IP address. Google Analytics and Mixpanel also receive this information and their use of it is governed by their Privacy Policies.\r\n&lt;/p&gt;\r\n\r\n&lt;h3 class=&quot;text-info&quot;&gt;\r\n    How We Share Your Information\r\n&lt;/h3&gt;\r\n&lt;h4 class=&quot;text-danger&quot;&gt;\r\n    Personally Identifiable Information:\r\n&lt;/h5&gt;\r\n&lt;p&gt;\r\n    We may share your personally identifiable information with third parties for the purpose of providing the Service to you. If we do this, such third parties&#039; use of your information will be bound by this Privacy Policy. We may store personal information in locations outside the direct control of Sngine (for instance, on servers or databases co-located with hosting providers).\r\n&lt;/p&gt;\r\n&lt;p&gt;\r\n    We may share or disclose your information with your consent, such as if you choose to sign on to the Service through a third-party service. We cannot control third parties&#039; use of your information.\r\n&lt;/p&gt;\r\n&lt;p&gt;\r\n    Sngine may disclose your personal information if required to do so by law or subpoena or if we believe that it is reasonably necessary to comply with a law, regulation or legal request; to protect the safety of any person; to address fraud, security or technical issues; or to protect Sngine&#039;s rights or property.\r\n&lt;/p&gt;\r\n&lt;h4 class=&quot;text-danger&quot;&gt;\r\n    Non-Personally Identifiable Information:\r\n&lt;/h5&gt;\r\n&lt;p&gt;\r\n    We may share non-personally identifiable information (such as anonymous usage data, referring/exit pages and URLs, platform types, number of clicks, etc.) with interested third parties to help them understand the usage patterns for certain Sngine services.\r\n&lt;/p&gt;\r\n&lt;p&gt;\r\n    Sngine may allow third-party ad servers or ad networks to serve advertisements on the Service. These third-party ad servers or ad networks use technology to send, directly to your browser, the advertisements and links that appear on Sngine. They automatically receive your IP address when this happens. They may also use other technologies (such as cookies, JavaScript, or web beacons) to measure the effectiveness of their advertisements and to personalize the advertising content. Sngine does not provide any personally identifiable information to these third-party ad servers or ad networks without your consent. However, please note that if an advertiser asks Sngine to show an advertisement to a certain audience and you respond to that advertisement, the advertiser or ad server may conclude that you fit the description of the audience they are trying to reach. The Sngine Privacy Policy does not apply to, and we cannot control the activities of, third-party advertisers. Please consult the respective privacy policies of such advertisers for more information.\r\n&lt;/p&gt;\r\n\r\n&lt;h3 class=&quot;text-info&quot;&gt;\r\n    How We Protect Your Information\r\n&lt;/h3&gt;\r\n&lt;p&gt;\r\n    We uses commercially reasonable physical, managerial, and technical safeguards to preserve the integrity and security of your personal information. We cannot, however, ensure or warrant the security of any information you transmit to Sngine or guarantee that your information on the Service may not be accessed, disclosed, altered, or destroyed by breach of any of our physical, technical, or managerial safeguards.\r\n&lt;/p&gt;\r\n\r\n&lt;h3 class=&quot;text-info&quot;&gt;\r\n    Your Choices About Your Information\r\n&lt;/h3&gt;\r\n&lt;p&gt;\r\n    You may, of course, decline to submit personally identifiable information through the Service, in which case Sngine may not be able to provide certain services to you. You may update or correct your account information and email preferences at any time by logging in to your account.\r\n&lt;/p&gt;\r\n\r\n\r\n&lt;h3 class=&quot;text-info&quot;&gt;\r\n    Links to Other Web Sites\r\n&lt;/h3&gt;\r\n&lt;p&gt;\r\n    We are not responsible for the practices employed by websites linked to or from the Service, nor the information or content contained therein. Please remember that when you use a link to go from the Service to another website, our Privacy Policy is no longer in effect. Your browsing and interaction on any other website, including those that have a link on our website, is subject to that website&#039;s own rules and policies.\r\n&lt;/p&gt;\r\n\r\n&lt;h3 class=&quot;text-info&quot;&gt;\r\n    Changes to Our Privacy Policy\r\n&lt;/h3&gt;\r\n&lt;p&gt;\r\n    If we change our privacy policies and procedures, we will post those changes on this page to keep you aware of what information we collect, how we use it and under what circumstances we may disclose it. Changes to this Privacy Policy are effective when they are posted on this page.\r\n&lt;/p&gt;\r\n&lt;small&gt;\r\n    &lt;i&gt;Last updated on: Jan 29, 2016&lt;/i&gt;\r\n&lt;/small&gt;', '1');

-- --------------------------------------------------------

--
-- Table structure for table `stickers`
--

CREATE TABLE `stickers` (
  `sticker_id` int(10) UNSIGNED NOT NULL,
  `image` varchar(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

--
-- Dumping data for table `stickers`
--

INSERT INTO `stickers` (`sticker_id`, `image`) VALUES
(1, 'stickers/1.png'),
(2, 'stickers/2.png'),
(3, 'stickers/3.png'),
(4, 'stickers/4.png'),
(5, 'stickers/5.png'),
(6, 'stickers/6.png'),
(7, 'stickers/7.png'),
(8, 'stickers/8.png'),
(9, 'stickers/9.png'),
(10, 'stickers/10.png'),
(11, 'stickers/11.png'),
(12, 'stickers/12.png'),
(13, 'stickers/13.png'),
(14, 'stickers/14.png'),
(15, 'stickers/15.png'),
(16, 'stickers/16.png'),
(17, 'stickers/17.png'),
(18, 'stickers/18.png');

-- --------------------------------------------------------

--
-- Table structure for table `stories`
--

CREATE TABLE `stories` (
  `story_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `text` longtext,
  `time` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `stories_media`
--

CREATE TABLE `stories_media` (
  `media_id` int(10) UNSIGNED NOT NULL,
  `story_id` int(10) UNSIGNED NOT NULL,
  `source` varchar(255) NOT NULL,
  `is_photo` enum('0','1') NOT NULL DEFAULT '1'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `system_languages`
--

CREATE TABLE `system_languages` (
  `language_id` int(10) UNSIGNED NOT NULL,
  `code` varchar(32) NOT NULL,
  `title` varchar(255) NOT NULL,
  `flag_icon` varchar(32) NOT NULL,
  `dir` enum('LTR','RTL') NOT NULL,
  `default` enum('0','1') NOT NULL,
  `enabled` enum('0','1') NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Dumping data for table `system_languages`
--

INSERT INTO `system_languages` (`language_id`, `code`, `title`, `flag_icon`, `dir`, `default`, `enabled`) VALUES
(1, 'en_us', 'English', 'us', 'LTR', '1', '1'),
(2, 'ar_sa', 'Arabic', 'sa', 'RTL', '0', '1'),
(3, 'fr_fr', 'Fran&ccedil;ais', 'fr', 'LTR', '0', '1'),
(4, 'es_es', 'Espa&ntilde;ol', 'es', 'LTR', '0', '1'),
(5, 'pt_pt', 'Portugu&ecirc;s', 'pt', 'LTR', '0', '1'),
(6, 'de_de', 'Deutsch', 'de', 'LTR', '0', '1'),
(7, 'tr_tr', 'T&uuml;rk&ccedil;e', 'tr', 'LTR', '0', '1'),
(8, 'nl_nl', 'Dutch', 'nl', 'LTR', '0', '1'),
(9, 'it_it', 'Italiano', 'it', 'LTR', '0', '1'),
(10, 'ru_ru', 'Russian', 'ru', 'LTR', '0', '1'),
(11, 'ro_ro', 'Romaian', 'ro', 'LTR', '0', '1');

-- --------------------------------------------------------

--
-- Table structure for table `system_options`
--

CREATE TABLE `system_options` (
  `ID` int(10) UNSIGNED NOT NULL,
  `system_public` enum('0','1') NOT NULL DEFAULT '1',
  `system_live` enum('0','1') NOT NULL DEFAULT '1',
  `system_message` text NOT NULL,
  `system_title` varchar(255) NOT NULL DEFAULT 'Sngine',
  `system_description` text NOT NULL,
  `system_keywords` text NOT NULL,
  `system_email` varchar(255) DEFAULT NULL,
  `contact_enabled` enum('0','1') NOT NULL DEFAULT '1',
  `directory_enabled` enum('0','1') NOT NULL DEFAULT '1',
  `pages_enabled` enum('0','1') NOT NULL DEFAULT '1',
  `groups_enabled` enum('0','1') NOT NULL DEFAULT '1',
  `events_enabled` enum('0','1') NOT NULL DEFAULT '1',
  `blogs_enabled` enum('0','1') NOT NULL DEFAULT '1',
  `market_enabled` enum('0','1') NOT NULL DEFAULT '1',
  `games_enabled` enum('0','1') NOT NULL DEFAULT '1',
  `daytime_msg_enabled` enum('0','1') NOT NULL DEFAULT '1',
  `verification_requests` enum('0','1') NOT NULL DEFAULT '1',
  `profile_notification_enabled` enum('0','1') NOT NULL DEFAULT '1',
  `stories_enabled` enum('0','1') NOT NULL DEFAULT '1',
  `wall_posts_enabled` enum('0','1') NOT NULL DEFAULT '1',
  `social_share_enabled` enum('0','1') NOT NULL DEFAULT '1',
  `smart_yt_player` enum('0','1') NOT NULL DEFAULT '1',
  `geolocation_enabled` enum('0','1') NOT NULL DEFAULT '0',
  `geolocation_key` varchar(255) DEFAULT NULL,
  `default_privacy` enum('public','friends','me') NOT NULL DEFAULT 'friends',
  `registration_enabled` enum('0','1') NOT NULL DEFAULT '1',
  `registration_type` enum('free','paid') NOT NULL DEFAULT 'free',
  `packages_enabled` enum('0','1') NOT NULL DEFAULT '0',
  `activation_enabled` enum('0','1') NOT NULL DEFAULT '1',
  `activation_type` enum('email','sms') NOT NULL DEFAULT 'email',
  `age_restriction` enum('0','1') NOT NULL DEFAULT '0',
  `minimum_age` tinyint(1) UNSIGNED DEFAULT NULL,
  `getting_started` enum('0','1') NOT NULL DEFAULT '1',
  `delete_accounts_enabled` enum('0','1') NOT NULL DEFAULT '1',
  `max_accounts` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `social_login_enabled` enum('0','1') NOT NULL DEFAULT '0',
  `facebook_login_enabled` enum('0','1') NOT NULL DEFAULT '0',
  `facebook_appid` varchar(255) DEFAULT NULL,
  `facebook_secret` varchar(255) DEFAULT NULL,
  `twitter_login_enabled` enum('0','1') NOT NULL DEFAULT '0',
  `twitter_appid` varchar(255) DEFAULT NULL,
  `twitter_secret` varchar(255) DEFAULT NULL,
  `google_login_enabled` enum('0','1') NOT NULL DEFAULT '0',
  `google_appid` varchar(255) DEFAULT NULL,
  `google_secret` varchar(255) DEFAULT NULL,
  `instagram_login_enabled` enum('0','1') NOT NULL DEFAULT '0',
  `instagram_appid` varchar(255) DEFAULT NULL,
  `instagram_secret` varchar(255) DEFAULT NULL,
  `linkedin_login_enabled` enum('0','1') NOT NULL DEFAULT '0',
  `linkedin_appid` varchar(255) DEFAULT NULL,
  `linkedin_secret` varchar(255) DEFAULT NULL,
  `vkontakte_login_enabled` enum('0','1') NOT NULL DEFAULT '0',
  `vkontakte_appid` varchar(255) DEFAULT NULL,
  `vkontakte_secret` varchar(255) DEFAULT NULL,
  `email_smtp_enabled` enum('0','1') NOT NULL DEFAULT '0',
  `email_smtp_authentication` enum('0','1') NOT NULL DEFAULT '1',
  `email_smtp_ssl` enum('0','1') NOT NULL DEFAULT '0',
  `email_smtp_server` varchar(255) DEFAULT NULL,
  `email_smtp_port` varchar(255) DEFAULT NULL,
  `email_smtp_username` varchar(255) DEFAULT NULL,
  `email_smtp_password` varchar(255) DEFAULT NULL,
  `email_smtp_setfrom` varchar(255) DEFAULT NULL,
  `email_notifications` enum('0','1') NOT NULL DEFAULT '1',
  `email_post_likes` enum('0','1') NOT NULL DEFAULT '1',
  `email_post_comments` enum('0','1') NOT NULL DEFAULT '1',
  `email_post_shares` enum('0','1') NOT NULL DEFAULT '1',
  `email_wall_posts` enum('0','1') NOT NULL DEFAULT '1',
  `email_mentions` enum('0','1') NOT NULL DEFAULT '1',
  `email_profile_visits` enum('0','1') NOT NULL DEFAULT '1',
  `email_friend_requests` enum('0','1') NOT NULL DEFAULT '1',
  `twilio_sid` varchar(255) DEFAULT NULL,
  `twilio_token` varchar(255) DEFAULT NULL,
  `twilio_phone` varchar(255) DEFAULT NULL,
  `system_phone` varchar(255) DEFAULT NULL,
  `chat_enabled` enum('0','1') NOT NULL DEFAULT '1',
  `chat_status_enabled` enum('0','1') NOT NULL DEFAULT '1',
  `s3_enabled` enum('0','1') NOT NULL DEFAULT '0',
  `s3_bucket` varchar(255) DEFAULT NULL,
  `s3_region` varchar(255) DEFAULT NULL,
  `s3_key` varchar(255) DEFAULT NULL,
  `s3_secret` varchar(255) DEFAULT NULL,
  `uploads_directory` varchar(255) NOT NULL DEFAULT 'content/uploads',
  `uploads_prefix` varchar(255) DEFAULT 'sngine',
  `max_avatar_size` int(10) UNSIGNED NOT NULL DEFAULT '5120',
  `max_cover_size` int(10) UNSIGNED NOT NULL DEFAULT '5120',
  `photos_enabled` enum('0','1') NOT NULL DEFAULT '1',
  `max_photo_size` int(10) UNSIGNED NOT NULL DEFAULT '5120',
  `videos_enabled` enum('0','1') NOT NULL DEFAULT '1',
  `max_video_size` int(10) UNSIGNED NOT NULL DEFAULT '5120',
  `video_extensions` text NOT NULL,
  `audio_enabled` enum('0','1') NOT NULL DEFAULT '1',
  `max_audio_size` int(10) UNSIGNED NOT NULL DEFAULT '5120',
  `audio_extensions` text NOT NULL,
  `file_enabled` enum('0','1') NOT NULL DEFAULT '1',
  `max_file_size` int(10) UNSIGNED NOT NULL DEFAULT '5120',
  `file_extensions` text NOT NULL,
  `censored_words_enabled` enum('0','1') NOT NULL DEFAULT '1',
  `censored_words` text NOT NULL,
  `reCAPTCHA_enabled` enum('0','1') NOT NULL DEFAULT '0',
  `reCAPTCHA_site_key` varchar(255) DEFAULT NULL,
  `reCAPTCHA_secret_key` varchar(255) DEFAULT NULL,
  `session_hash` varchar(255) NOT NULL,
  `paypal_enabled` enum('0','1') NOT NULL DEFAULT '0',
  `paypal_mode` enum('live','sandbox') NOT NULL DEFAULT 'sandbox',
  `paypal_id` varchar(255) DEFAULT NULL,
  `paypal_secret` varchar(255) DEFAULT NULL,
  `creditcard_enabled` enum('0','1') NOT NULL DEFAULT '0',
  `alipay_enabled` enum('0','1') NOT NULL DEFAULT '0',
  `bitcoin_enabled` enum('0','1') NOT NULL DEFAULT '0',
  `stripe_mode` enum('live','test') NOT NULL DEFAULT 'test',
  `stripe_test_secret` varchar(255) DEFAULT NULL,
  `stripe_test_publishable` varchar(255) DEFAULT NULL,
  `stripe_live_secret` varchar(255) DEFAULT NULL,
  `stripe_live_publishable` varchar(255) DEFAULT NULL,
  `system_currency` varchar(64) DEFAULT 'USD',
  `data_heartbeat` int(10) UNSIGNED NOT NULL DEFAULT '5',
  `chat_heartbeat` int(10) UNSIGNED NOT NULL DEFAULT '5',
  `offline_time` int(10) UNSIGNED NOT NULL DEFAULT '10',
  `min_results` int(10) UNSIGNED NOT NULL DEFAULT '5',
  `max_results` int(10) UNSIGNED NOT NULL DEFAULT '10',
  `min_results_even` int(10) UNSIGNED NOT NULL DEFAULT '6',
  `max_results_even` int(10) UNSIGNED NOT NULL DEFAULT '12',
  `analytics_code` text NOT NULL,
  `system_logo` varchar(255) DEFAULT NULL,
  `system_wallpaper_default` enum('0','1') NOT NULL DEFAULT '1',
  `system_wallpaper` varchar(255) DEFAULT NULL,
  `system_random_profiles` enum('0','1') NOT NULL DEFAULT '1',
  `system_favicon_default` enum('0','1') NOT NULL DEFAULT '1',
  `system_favicon` varchar(255) DEFAULT NULL,
  `system_ogimage_default` enum('0','1') NOT NULL DEFAULT '1',
  `system_ogimage` varchar(255) DEFAULT NULL,
  `css_customized` enum('0','1') NOT NULL DEFAULT '0',
  `css_background` varchar(32) DEFAULT NULL,
  `css_link_color` varchar(32) DEFAULT NULL,
  `css_header` varchar(32) DEFAULT NULL,
  `css_header_search` varchar(32) DEFAULT NULL,
  `css_header_search_color` varchar(32) DEFAULT NULL,
  `css_btn_primary` varchar(32) DEFAULT NULL,
  `css_menu_background` varchar(32) DEFAULT NULL,
  `css_custome_css` text NOT NULL,
  `affiliates_enabled` enum('0','1') NOT NULL DEFAULT '0',
  `affiliate_type` enum('registration','packages') NOT NULL DEFAULT 'registration',
  `affiliate_payment_method` enum('paypal','skrill','both') NOT NULL DEFAULT 'both',
  `affiliates_min_withdrawal` int(10) UNSIGNED NOT NULL DEFAULT '50',
  `affiliates_per_user` varchar(32) NOT NULL DEFAULT '0.1'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

--
-- Dumping data for table `system_options`
--

INSERT INTO `system_options` (`ID`, `system_public`, `system_live`, `system_message`, `system_title`, `system_description`, `system_keywords`, `system_email`, `contact_enabled`, `directory_enabled`, `pages_enabled`, `groups_enabled`, `events_enabled`, `blogs_enabled`, `market_enabled`, `games_enabled`, `daytime_msg_enabled`, `verification_requests`, `profile_notification_enabled`, `stories_enabled`, `wall_posts_enabled`, `social_share_enabled`, `smart_yt_player`, `geolocation_enabled`, `geolocation_key`, `default_privacy`, `registration_enabled`, `registration_type`, `packages_enabled`, `activation_enabled`, `activation_type`, `age_restriction`, `minimum_age`, `getting_started`, `delete_accounts_enabled`, `max_accounts`, `social_login_enabled`, `facebook_login_enabled`, `facebook_appid`, `facebook_secret`, `twitter_login_enabled`, `twitter_appid`, `twitter_secret`, `google_login_enabled`, `google_appid`, `google_secret`, `instagram_login_enabled`, `instagram_appid`, `instagram_secret`, `linkedin_login_enabled`, `linkedin_appid`, `linkedin_secret`, `vkontakte_login_enabled`, `vkontakte_appid`, `vkontakte_secret`, `email_smtp_enabled`, `email_smtp_authentication`, `email_smtp_ssl`, `email_smtp_server`, `email_smtp_port`, `email_smtp_username`, `email_smtp_password`, `email_smtp_setfrom`, `email_notifications`, `email_post_likes`, `email_post_comments`, `email_post_shares`, `email_wall_posts`, `email_mentions`, `email_profile_visits`, `email_friend_requests`, `twilio_sid`, `twilio_token`, `twilio_phone`, `system_phone`, `chat_enabled`, `chat_status_enabled`, `s3_enabled`, `s3_bucket`, `s3_region`, `s3_key`, `s3_secret`, `uploads_directory`, `uploads_prefix`, `max_avatar_size`, `max_cover_size`, `photos_enabled`, `max_photo_size`, `videos_enabled`, `max_video_size`, `video_extensions`, `audio_enabled`, `max_audio_size`, `audio_extensions`, `file_enabled`, `max_file_size`, `file_extensions`, `censored_words_enabled`, `censored_words`, `reCAPTCHA_enabled`, `reCAPTCHA_site_key`, `reCAPTCHA_secret_key`, `session_hash`, `paypal_enabled`, `paypal_mode`, `paypal_id`, `paypal_secret`, `creditcard_enabled`, `alipay_enabled`, `bitcoin_enabled`, `stripe_mode`, `stripe_test_secret`, `stripe_test_publishable`, `stripe_live_secret`, `stripe_live_publishable`, `system_currency`, `data_heartbeat`, `chat_heartbeat`, `offline_time`, `min_results`, `max_results`, `min_results_even`, `max_results_even`, `analytics_code`, `system_logo`, `system_wallpaper_default`, `system_wallpaper`, `system_random_profiles`, `system_favicon_default`, `system_favicon`, `system_ogimage_default`, `system_ogimage`, `css_customized`, `css_background`, `css_link_color`, `css_header`, `css_header_search`, `css_header_search_color`, `css_btn_primary`, `css_menu_background`, `css_custome_css`, `affiliates_enabled`, `affiliate_type`, `affiliate_payment_method`, `affiliates_min_withdrawal`, `affiliates_per_user`) VALUES
(1, '1', '1', '', 'Sngine', '', '', NULL, '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '0', NULL, 'public', '1', 'free', '0', '1', 'email', '0', 13, '1', '1', 0, '0', '0', NULL, NULL, '0', NULL, NULL, '0', NULL, NULL, '0', NULL, NULL, '0', NULL, NULL, '0', NULL, NULL, '0', '0', '0', NULL, NULL, NULL, NULL, NULL, '0', '0', '0', '0', '0', '0', '0', '0', NULL, NULL, NULL, NULL, '1', '1', '0', NULL, NULL, NULL, NULL, 'content/uploads', 'sngine', 5120, 5120, '1', 5120, '1', 5120, 'mp4, mov', '1', 512000, 'mp3, wav', '1', 5120, 'txt, zip', '1', 'pussy,fuck,shit,asshole,dick,tits,boobs', '0', NULL, NULL, '', '0', 'sandbox', '', '', '0', '0', '0', 'test', NULL, NULL, NULL, NULL, 'USD', 5, 5, 10, 5, 10, 6, 12, '', NULL, '1', NULL, '1', '1', NULL, '1', NULL, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '0', 'registration', 'both', 50, '0.10');

-- --------------------------------------------------------

--
-- Table structure for table `system_themes`
--

CREATE TABLE `system_themes` (
  `theme_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(64) NOT NULL,
  `default` enum('0','1') NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

--
-- Dumping data for table `system_themes`
--

INSERT INTO `system_themes` (`theme_id`, `name`, `default`) VALUES
(1, 'default', '1');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `user_group` tinyint(10) UNSIGNED NOT NULL DEFAULT '3',
  `user_name` varchar(64) NOT NULL,
  `user_email` varchar(64) NOT NULL,
  `user_phone` varchar(64) DEFAULT NULL,
  `user_password` varchar(64) NOT NULL,
  `user_activated` enum('0','1') NOT NULL DEFAULT '0',
  `user_activation_key` varchar(64) DEFAULT NULL,
  `user_reseted` enum('0','1') NOT NULL DEFAULT '0',
  `user_reset_key` varchar(64) DEFAULT NULL,
  `user_subscribed` enum('0','1') NOT NULL DEFAULT '0',
  `user_package` int(10) UNSIGNED DEFAULT NULL,
  `user_subscription_date` datetime DEFAULT NULL,
  `user_boosted_posts` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `user_boosted_pages` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `user_started` enum('0','1') NOT NULL DEFAULT '0',
  `user_verified` enum('0','1') NOT NULL DEFAULT '0',
  `user_banned` enum('0','1') NOT NULL DEFAULT '0',
  `user_live_requests_counter` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `user_live_requests_lastid` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `user_live_messages_counter` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `user_live_messages_lastid` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `user_live_notifications_counter` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `user_live_notifications_lastid` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `user_firstname` varchar(255) NOT NULL,
  `user_lastname` varchar(255) DEFAULT NULL,
  `user_gender` enum('male','female') NOT NULL,
  `user_picture` varchar(255) DEFAULT NULL,
  `user_picture_id` int(10) UNSIGNED DEFAULT NULL,
  `user_cover` varchar(255) DEFAULT NULL,
  `user_cover_id` int(10) UNSIGNED DEFAULT NULL,
  `user_album_pictures` int(10) UNSIGNED DEFAULT NULL,
  `user_album_covers` int(10) UNSIGNED DEFAULT NULL,
  `user_album_timeline` int(10) UNSIGNED DEFAULT NULL,
  `user_pinned_post` int(10) UNSIGNED DEFAULT NULL,
  `user_registered` datetime DEFAULT NULL,
  `user_last_login` datetime DEFAULT NULL,
  `user_birthdate` date DEFAULT NULL,
  `user_relationship` varchar(255) DEFAULT NULL,
  `user_biography` text,
  `user_website` varchar(255) DEFAULT NULL,
  `user_work_title` varchar(255) DEFAULT NULL,
  `user_work_place` varchar(255) DEFAULT NULL,
  `user_current_city` varchar(255) DEFAULT NULL,
  `user_hometown` varchar(255) DEFAULT NULL,
  `user_edu_major` varchar(255) DEFAULT NULL,
  `user_edu_school` varchar(255) DEFAULT NULL,
  `user_edu_class` varchar(255) DEFAULT NULL,
  `user_social_facebook` varchar(255) DEFAULT NULL,
  `user_social_twitter` varchar(255) DEFAULT NULL,
  `user_social_google` varchar(255) DEFAULT NULL,
  `user_social_youtube` varchar(255) DEFAULT NULL,
  `user_social_instagram` varchar(255) DEFAULT NULL,
  `user_social_linkedin` varchar(255) DEFAULT NULL,
  `user_social_vkontakte` varchar(255) DEFAULT NULL,
  `user_chat_enabled` enum('0','1') NOT NULL DEFAULT '1',
  `user_privacy_wall` enum('me','friends','public') NOT NULL DEFAULT 'friends',
  `user_privacy_birthdate` enum('me','friends','public') NOT NULL DEFAULT 'public',
  `user_privacy_relationship` enum('me','friends','public') NOT NULL DEFAULT 'public',
  `user_privacy_basic` enum('me','friends','public') NOT NULL DEFAULT 'public',
  `user_privacy_work` enum('me','friends','public') NOT NULL DEFAULT 'public',
  `user_privacy_location` enum('me','friends','public') NOT NULL DEFAULT 'public',
  `user_privacy_education` enum('me','friends','public') NOT NULL DEFAULT 'public',
  `user_privacy_other` enum('me','friends','public') NOT NULL DEFAULT 'public',
  `user_privacy_friends` enum('me','friends','public') NOT NULL DEFAULT 'public',
  `user_privacy_photos` enum('me','friends','public') NOT NULL DEFAULT 'public',
  `user_privacy_pages` enum('me','friends','public') NOT NULL DEFAULT 'public',
  `user_privacy_groups` enum('me','friends','public') NOT NULL DEFAULT 'public',
  `user_privacy_events` enum('me','friends','public') NOT NULL DEFAULT 'public',
  `email_post_likes` enum('0','1') NOT NULL DEFAULT '1',
  `email_post_comments` enum('0','1') NOT NULL DEFAULT '1',
  `email_post_shares` enum('0','1') NOT NULL DEFAULT '1',
  `email_wall_posts` enum('0','1') NOT NULL DEFAULT '1',
  `email_mentions` enum('0','1') NOT NULL DEFAULT '1',
  `email_profile_visits` enum('0','1') NOT NULL DEFAULT '1',
  `email_friend_requests` enum('0','1') NOT NULL DEFAULT '1',
  `facebook_connected` enum('0','1') NOT NULL DEFAULT '0',
  `facebook_id` varchar(255) DEFAULT NULL,
  `twitter_connected` enum('0','1') NOT NULL DEFAULT '0',
  `twitter_id` varchar(255) DEFAULT NULL,
  `google_connected` enum('0','1') NOT NULL DEFAULT '0',
  `google_id` varchar(255) DEFAULT NULL,
  `instagram_connected` enum('0','1') NOT NULL DEFAULT '0',
  `instagram_id` varchar(255) DEFAULT NULL,
  `linkedin_connected` enum('0','1') NOT NULL DEFAULT '0',
  `linkedin_id` varchar(255) DEFAULT NULL,
  `vkontakte_connected` enum('0','1') NOT NULL DEFAULT '0',
  `vkontakte_id` varchar(255) DEFAULT NULL,
  `user_referrer_id` int(10) DEFAULT NULL,
  `user_affiliate_balance` varchar(64) NOT NULL DEFAULT '0',
  `notifications_sound` enum('0','1') NOT NULL DEFAULT '1'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `users_blocks`
--

CREATE TABLE `users_blocks` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `blocked_id` int(10) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=FIXED;

-- --------------------------------------------------------

--
-- Table structure for table `users_custom_fields`
--

CREATE TABLE `users_custom_fields` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `field_id` int(10) UNSIGNED NOT NULL,
  `value` text NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=FIXED;

-- --------------------------------------------------------

--
-- Table structure for table `users_online`
--

CREATE TABLE `users_online` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `last_seen` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `users_polls_options`
--

CREATE TABLE `users_polls_options` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `poll_id` int(10) UNSIGNED NOT NULL,
  `option_id` int(10) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=FIXED;

-- --------------------------------------------------------

--
-- Table structure for table `users_searches`
--

CREATE TABLE `users_searches` (
  `log_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `node_id` int(10) UNSIGNED NOT NULL,
  `node_type` varchar(32) NOT NULL,
  `time` datetime DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `users_sessions`
--

CREATE TABLE `users_sessions` (
  `session_id` int(10) UNSIGNED NOT NULL,
  `session_token` varchar(64) NOT NULL,
  `session_date` datetime NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `user_browser` varchar(64) NOT NULL,
  `user_os` varchar(64) NOT NULL,
  `user_ip` varchar(64) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `verification_requests`
--

CREATE TABLE `verification_requests` (
  `request_id` int(10) UNSIGNED NOT NULL,
  `node_id` int(10) UNSIGNED NOT NULL,
  `node_type` varchar(32) NOT NULL,
  `time` datetime NOT NULL,
  `status` tinyint(1) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `widgets`
--

CREATE TABLE `widgets` (
  `widget_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `place` varchar(32) NOT NULL,
  `code` text NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `ads`
--
ALTER TABLE `ads`
  ADD PRIMARY KEY (`ads_id`);

--
-- Indexes for table `affiliates_payments`
--
ALTER TABLE `affiliates_payments`
  ADD PRIMARY KEY (`payment_id`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`announcement_id`);

--
-- Indexes for table `announcements_users`
--
ALTER TABLE `announcements_users`
  ADD UNIQUE KEY `announcement_id_user_id` (`announcement_id`,`user_id`);

--
-- Indexes for table `banned_ips`
--
ALTER TABLE `banned_ips`
  ADD PRIMARY KEY (`ip_id`);

--
-- Indexes for table `conversations`
--
ALTER TABLE `conversations`
  ADD PRIMARY KEY (`conversation_id`);

--
-- Indexes for table `conversations_messages`
--
ALTER TABLE `conversations_messages`
  ADD PRIMARY KEY (`message_id`);

--
-- Indexes for table `conversations_users`
--
ALTER TABLE `conversations_users`
  ADD UNIQUE KEY `conversation_id_user_id` (`conversation_id`,`user_id`);

--
-- Indexes for table `custom_fields`
--
ALTER TABLE `custom_fields`
  ADD PRIMARY KEY (`field_id`);

--
-- Indexes for table `emojis`
--
ALTER TABLE `emojis`
  ADD PRIMARY KEY (`emoji_id`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`event_id`);

--
-- Indexes for table `events_categories`
--
ALTER TABLE `events_categories`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `events_members`
--
ALTER TABLE `events_members`
  ADD UNIQUE KEY `group_id_user_id` (`event_id`,`user_id`);

--
-- Indexes for table `followings`
--
ALTER TABLE `followings`
  ADD UNIQUE KEY `user_id_following_id` (`user_id`,`following_id`);

--
-- Indexes for table `friends`
--
ALTER TABLE `friends`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_one_id_user_two_id` (`user_one_id`,`user_two_id`);

--
-- Indexes for table `games`
--
ALTER TABLE `games`
  ADD PRIMARY KEY (`game_id`);

--
-- Indexes for table `game_players`
--
ALTER TABLE `game_players`
  ADD UNIQUE KEY `game_id_user_id` (`game_id`,`user_id`);

--
-- Indexes for table `groups`
--
ALTER TABLE `groups`
  ADD PRIMARY KEY (`group_id`),
  ADD UNIQUE KEY `username` (`group_name`);

--
-- Indexes for table `groups_members`
--
ALTER TABLE `groups_members`
  ADD UNIQUE KEY `group_id_user_id` (`group_id`,`user_id`);

--
-- Indexes for table `market_categories`
--
ALTER TABLE `market_categories`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`);

--
-- Indexes for table `packages`
--
ALTER TABLE `packages`
  ADD PRIMARY KEY (`package_id`);

--
-- Indexes for table `packages_payments`
--
ALTER TABLE `packages_payments`
  ADD PRIMARY KEY (`payment_id`);

--
-- Indexes for table `pages`
--
ALTER TABLE `pages`
  ADD PRIMARY KEY (`page_id`),
  ADD UNIQUE KEY `username` (`page_name`);

--
-- Indexes for table `pages_categories`
--
ALTER TABLE `pages_categories`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `pages_invites`
--
ALTER TABLE `pages_invites`
  ADD UNIQUE KEY `page_id_user_id_from_user_id` (`page_id`,`user_id`,`from_user_id`);

--
-- Indexes for table `pages_likes`
--
ALTER TABLE `pages_likes`
  ADD UNIQUE KEY `page_id_user_id` (`page_id`,`user_id`);

--
-- Indexes for table `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`post_id`);

--
-- Indexes for table `posts_articles`
--
ALTER TABLE `posts_articles`
  ADD PRIMARY KEY (`article_id`);

--
-- Indexes for table `posts_audios`
--
ALTER TABLE `posts_audios`
  ADD PRIMARY KEY (`audio_id`);

--
-- Indexes for table `posts_comments`
--
ALTER TABLE `posts_comments`
  ADD PRIMARY KEY (`comment_id`);

--
-- Indexes for table `posts_comments_likes`
--
ALTER TABLE `posts_comments_likes`
  ADD UNIQUE KEY `comment_id_user_id` (`comment_id`,`user_id`);

--
-- Indexes for table `posts_files`
--
ALTER TABLE `posts_files`
  ADD PRIMARY KEY (`file_id`);

--
-- Indexes for table `posts_hidden`
--
ALTER TABLE `posts_hidden`
  ADD UNIQUE KEY `post_id_user_id` (`post_id`,`user_id`);

--
-- Indexes for table `posts_likes`
--
ALTER TABLE `posts_likes`
  ADD UNIQUE KEY `post_id_user_id` (`post_id`,`user_id`);

--
-- Indexes for table `posts_links`
--
ALTER TABLE `posts_links`
  ADD PRIMARY KEY (`link_id`);

--
-- Indexes for table `posts_media`
--
ALTER TABLE `posts_media`
  ADD PRIMARY KEY (`media_id`);

--
-- Indexes for table `posts_photos`
--
ALTER TABLE `posts_photos`
  ADD PRIMARY KEY (`photo_id`);

--
-- Indexes for table `posts_photos_albums`
--
ALTER TABLE `posts_photos_albums`
  ADD PRIMARY KEY (`album_id`);

--
-- Indexes for table `posts_photos_likes`
--
ALTER TABLE `posts_photos_likes`
  ADD UNIQUE KEY `user_id_photo_id` (`user_id`,`photo_id`);

--
-- Indexes for table `posts_polls`
--
ALTER TABLE `posts_polls`
  ADD PRIMARY KEY (`poll_id`);

--
-- Indexes for table `posts_polls_options`
--
ALTER TABLE `posts_polls_options`
  ADD PRIMARY KEY (`option_id`);

--
-- Indexes for table `posts_products`
--
ALTER TABLE `posts_products`
  ADD PRIMARY KEY (`product_id`);

--
-- Indexes for table `posts_saved`
--
ALTER TABLE `posts_saved`
  ADD UNIQUE KEY `post_id_user_id` (`post_id`,`user_id`);

--
-- Indexes for table `posts_videos`
--
ALTER TABLE `posts_videos`
  ADD PRIMARY KEY (`video_id`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`report_id`);

--
-- Indexes for table `static_pages`
--
ALTER TABLE `static_pages`
  ADD PRIMARY KEY (`page_id`),
  ADD UNIQUE KEY `page_url` (`page_url`);

--
-- Indexes for table `stickers`
--
ALTER TABLE `stickers`
  ADD PRIMARY KEY (`sticker_id`);

--
-- Indexes for table `stories`
--
ALTER TABLE `stories`
  ADD PRIMARY KEY (`story_id`);

--
-- Indexes for table `stories_media`
--
ALTER TABLE `stories_media`
  ADD PRIMARY KEY (`media_id`);

--
-- Indexes for table `system_languages`
--
ALTER TABLE `system_languages`
  ADD PRIMARY KEY (`language_id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `system_options`
--
ALTER TABLE `system_options`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `system_themes`
--
ALTER TABLE `system_themes`
  ADD PRIMARY KEY (`theme_id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`user_name`),
  ADD UNIQUE KEY `user_email` (`user_email`),
  ADD UNIQUE KEY `facebook_id` (`facebook_id`),
  ADD UNIQUE KEY `twitter_id` (`twitter_id`),
  ADD UNIQUE KEY `google_id` (`google_id`),
  ADD UNIQUE KEY `linkedin_id` (`linkedin_id`),
  ADD UNIQUE KEY `vkontakte_id` (`vkontakte_id`),
  ADD UNIQUE KEY `instagram_id` (`instagram_id`),
  ADD UNIQUE KEY `user_phone` (`user_phone`);

--
-- Indexes for table `users_blocks`
--
ALTER TABLE `users_blocks`
  ADD UNIQUE KEY `user_id_blocked_id` (`user_id`,`blocked_id`);

--
-- Indexes for table `users_custom_fields`
--
ALTER TABLE `users_custom_fields`
  ADD UNIQUE KEY `user_id_field_id` (`user_id`,`field_id`);

--
-- Indexes for table `users_online`
--
ALTER TABLE `users_online`
  ADD UNIQUE KEY `UserID` (`user_id`);

--
-- Indexes for table `users_polls_options`
--
ALTER TABLE `users_polls_options`
  ADD UNIQUE KEY `user_id_poll_id` (`user_id`,`poll_id`);

--
-- Indexes for table `users_searches`
--
ALTER TABLE `users_searches`
  ADD PRIMARY KEY (`log_id`),
  ADD UNIQUE KEY `node_id_node_type` (`node_id`,`node_type`);

--
-- Indexes for table `users_sessions`
--
ALTER TABLE `users_sessions`
  ADD PRIMARY KEY (`session_id`),
  ADD UNIQUE KEY `session_token` (`session_token`),
  ADD KEY `user_ip` (`user_ip`);

--
-- Indexes for table `verification_requests`
--
ALTER TABLE `verification_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD UNIQUE KEY `node_id_node_type` (`node_id`,`node_type`);

--
-- Indexes for table `widgets`
--
ALTER TABLE `widgets`
  ADD PRIMARY KEY (`widget_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `ads`
--
ALTER TABLE `ads`
  MODIFY `ads_id` int(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `affiliates_payments`
--
ALTER TABLE `affiliates_payments`
  MODIFY `payment_id` int(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `announcement_id` int(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `banned_ips`
--
ALTER TABLE `banned_ips`
  MODIFY `ip_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `conversations`
--
ALTER TABLE `conversations`
  MODIFY `conversation_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `conversations_messages`
--
ALTER TABLE `conversations_messages`
  MODIFY `message_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `custom_fields`
--
ALTER TABLE `custom_fields`
  MODIFY `field_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `emojis`
--
ALTER TABLE `emojis`
  MODIFY `emoji_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=152;
--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `event_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `events_categories`
--
ALTER TABLE `events_categories`
  MODIFY `category_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;
--
-- AUTO_INCREMENT for table `friends`
--
ALTER TABLE `friends`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `games`
--
ALTER TABLE `games`
  MODIFY `game_id` int(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `groups`
--
ALTER TABLE `groups`
  MODIFY `group_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `market_categories`
--
ALTER TABLE `market_categories`
  MODIFY `category_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;
--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `packages`
--
ALTER TABLE `packages`
  MODIFY `package_id` int(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `packages_payments`
--
ALTER TABLE `packages_payments`
  MODIFY `payment_id` int(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `pages`
--
ALTER TABLE `pages`
  MODIFY `page_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `pages_categories`
--
ALTER TABLE `pages_categories`
  MODIFY `category_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;
--
-- AUTO_INCREMENT for table `posts`
--
ALTER TABLE `posts`
  MODIFY `post_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `posts_articles`
--
ALTER TABLE `posts_articles`
  MODIFY `article_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `posts_audios`
--
ALTER TABLE `posts_audios`
  MODIFY `audio_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `posts_comments`
--
ALTER TABLE `posts_comments`
  MODIFY `comment_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `posts_files`
--
ALTER TABLE `posts_files`
  MODIFY `file_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `posts_links`
--
ALTER TABLE `posts_links`
  MODIFY `link_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `posts_media`
--
ALTER TABLE `posts_media`
  MODIFY `media_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `posts_photos`
--
ALTER TABLE `posts_photos`
  MODIFY `photo_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `posts_photos_albums`
--
ALTER TABLE `posts_photos_albums`
  MODIFY `album_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `posts_polls`
--
ALTER TABLE `posts_polls`
  MODIFY `poll_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `posts_polls_options`
--
ALTER TABLE `posts_polls_options`
  MODIFY `option_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `posts_products`
--
ALTER TABLE `posts_products`
  MODIFY `product_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `posts_videos`
--
ALTER TABLE `posts_videos`
  MODIFY `video_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `report_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `static_pages`
--
ALTER TABLE `static_pages`
  MODIFY `page_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
--
-- AUTO_INCREMENT for table `stickers`
--
ALTER TABLE `stickers`
  MODIFY `sticker_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;
--
-- AUTO_INCREMENT for table `stories`
--
ALTER TABLE `stories`
  MODIFY `story_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `stories_media`
--
ALTER TABLE `stories_media`
  MODIFY `media_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `system_languages`
--
ALTER TABLE `system_languages`
  MODIFY `language_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;
--
-- AUTO_INCREMENT for table `system_options`
--
ALTER TABLE `system_options`
  MODIFY `ID` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `system_themes`
--
ALTER TABLE `system_themes`
  MODIFY `theme_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `users_searches`
--
ALTER TABLE `users_searches`
  MODIFY `log_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `users_sessions`
--
ALTER TABLE `users_sessions`
  MODIFY `session_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `verification_requests`
--
ALTER TABLE `verification_requests`
  MODIFY `request_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `widgets`
--
ALTER TABLE `widgets`
  MODIFY `widget_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
    ";
    
    $db->multi_query($structure) or _error("Error", $db->error);
    // flush multi_queries
    do{} while(mysqli_more_results($db) && mysqli_next_result($db));


    // [4] update system settings
    $db->query(sprintf("UPDATE system_options SET system_email = %s, session_hash = %s", secure($_POST['admin_email']), secure($session_hash) )) or _error("Error", $db->error);


    // [5] Add the admin
    /* insert */
    $db->query(sprintf("INSERT INTO users (user_group, user_email, user_name, user_firstname, user_password, user_gender, user_activated, user_verified, user_started, user_registered) VALUES ('1', %s, %s, %s, %s, 'male', '1', '1', '1', %s)", secure($_POST['admin_email']), secure($_POST['admin_username']), secure($_POST['admin_username']), secure(_password_hash($_POST['admin_password'])), secure(gmdate('Y-m-d H:i:s')) )) or _error("Error", $db->error);


    // [6] create config file
    $config_string = '<?php  
    define("DB_NAME", "'. $_POST["db_name"]. '");
    define("DB_USER", "'. $_POST["db_username"]. '");
    define("DB_PASSWORD", "'. $_POST["db_password"]. '");
    define("DB_HOST", "'. $_POST["db_host"]. '");
    define("SYS_URL", "'. get_system_url(). '");
    define("DEBUGGING", false);
    define("DEFAULT_LOCALE", "en_us");
    define("LICENCE_KEY", "'. $licence_key. '");
    ?>';
    $config_file = 'includes/config.php';
    $handle = fopen($config_file, 'w') or _error("Error", "Sngine intsaller wizard cannot create the config file");
    fwrite($handle, $config_string);
    fclose($handle);
    

    // [7] start the system
    header('Location: ./');
}

?>

<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge"> 
        <meta name="viewport" content="width=device-width, initial-scale=1"> 
        
        <title>Sngine &rsaquo; Installer</title>
        
        <link rel="stylesheet" type="text/css" href="includes/assets/js/sngine/installer/installer.css" />
        <script src="includes/assets/js/sngine/installer/modernizr.custom.js"></script>
    </head>

    <body>
        
        <div class="container">

            <div class="fs-form-wrap" id="fs-form-wrap">
                
                <div class="fs-title">
                    <h1>Sngine Installer (<?php echo SYS_VER ?>)</h1>
                </div>
                
                <form id="myform" class="fs-form fs-form-full" autocomplete="off" action="install.php" method="post">
                    <ol class="fs-fields">

                        <li>
                            <p class="fs-field-label fs-anim-upper">
                                Welcome to <strong>Sngine</strong> installation process! Just fill in the information below and create your own social website or online community.
                            </p>
                        </li>

                        <li>
                            <label class="fs-field-label fs-anim-upper" for="purchase_code" data-info="The purchase code of Sngine">Purchase Code</label>
                            <input class="fs-anim-lower" id="purchase_code" name="purchase_code" type="text" placeholder="xxx-xx-xxxx" required/>
                        </li>
                        
                        <li>
                            <label class="fs-field-label fs-anim-upper" for="db_name" data-info="The name of the database you want to run Sngine in">What's Database Name?</label>
                            <input class="fs-anim-lower" id="db_name" name="db_name" type="text" placeholder="sngine" required/>
                        </li>

                        <li>
                            <label class="fs-field-label fs-anim-upper" for="db_username" data-info="Your MySQL username">What's Database Username?</label>
                            <input class="fs-anim-lower" id="db_username" name="db_username" type="text" placeholder="username" required/>
                        </li>

                        <li>
                            <label class="fs-field-label fs-anim-upper" for="db_password" data-info="Your MySQL password">What's Database Password?</label>
                            <input class="fs-anim-lower" id="db_password" name="db_password" type="text" placeholder="password"/>
                        </li>

                        <li>
                            <label class="fs-field-label fs-anim-upper" for="db_host" data-info="You should be able to get this info from your web host, if localhost does not work">What's Database Host?</label>
                            <input class="fs-anim-lower" id="db_host" name="db_host" type="text" placeholder="localhost" required/>
                        </li>
                        
                        <li>
                            <label class="fs-field-label fs-anim-upper" for="admin_email" data-info="Double-check your email address before continuing.">Your E-mail</label>
                            <input class="fs-anim-lower" id="admin_email" name="admin_email" type="email" placeholder="me@mail.com" required/>
                        </li>

                        <li>
                            <label class="fs-field-label fs-anim-upper" for="admin_username" data-info="Usernames can have only alphanumeric characters, underscores, and periods.">Username</label>
                            <input class="fs-anim-lower" id="admin_username" name="admin_username" type="text" placeholder="username" required/>
                        </li>

                        <li>
                            <label class="fs-field-label fs-anim-upper" for="admin_password" data-info=' The password should be at least seven characters long. To make it stronger, use upper and lower case letters, numbers, and symbols like ! " ? $ % ^ & ).'>Password</label>
                            <input class="fs-anim-lower" id="admin_password" name="admin_password" type="text" placeholder="password" required/>
                        </li>

                    </ol>
                    <button class="fs-submit" name="submit" type="submit">Install</button>
                </form>

            </div>

        </div>
        
        <script src="includes/assets/js/sngine/installer/classie.js"></script>
        <script src="includes/assets/js/sngine/installer/fullscreenForm.js"></script>
        <script>
            (function() {
                var formWrap = document.getElementById( 'fs-form-wrap' );
                new FForm( formWrap, {
                    onReview : function() {
                        classie.add( document.body, 'overview' );
                    }
                } );
            })();
        </script>

    </body>
</html>