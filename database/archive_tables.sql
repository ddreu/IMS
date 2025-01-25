-- Create archived_events table
CREATE TABLE IF NOT EXISTS `archived_events` (
  `event_id` int(11) NOT NULL,
  `event_name` varchar(255) NOT NULL,
  `event_description` text,
  `event_date` date NOT NULL,
  `event_time` time NOT NULL,
  `event_venue` varchar(255) NOT NULL,
  `event_status` varchar(50) NOT NULL,
  `archived_date` datetime NOT NULL,
  PRIMARY KEY (`event_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create archived_games table
CREATE TABLE IF NOT EXISTS `archived_games` (
  `game_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `team1_id` int(11) NOT NULL,
  `team2_id` int(11) NOT NULL,
  `game_date` date NOT NULL,
  `game_time` time NOT NULL,
  `venue` varchar(255) NOT NULL,
  `status` varchar(50) NOT NULL,
  `winner_id` int(11) DEFAULT NULL,
  `score_team1` int(11) DEFAULT NULL,
  `score_team2` int(11) DEFAULT NULL,
  `archived_date` datetime NOT NULL,
  PRIMARY KEY (`game_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
