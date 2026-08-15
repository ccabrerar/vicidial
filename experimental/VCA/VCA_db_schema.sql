CREATE TABLE `vicidial_vca_log` (
  `vca_log_id` int(9) unsigned NOT NULL AUTO_INCREMENT,
  `analysis_date` datetime DEFAULT NULL,
  `call_id` varchar(30) DEFAULT NULL,
  `server_ip` varchar(39) NOT NULL,
  `uniqueid` varchar(20) DEFAULT NULL,
  `channel` varchar(100) DEFAULT NULL,
  `amd_status` varchar(10) DEFAULT NULL,
  `amd_cause` varchar(30) DEFAULT NULL,
  `language` varchar(5) DEFAULT NULL,
  `lang_prob` float DEFAULT NULL,
  `text` varchar(100) DEFAULT NULL,
  `fft_max_mean_ratio` int(9) DEFAULT NULL,
  `freq_peaks` varchar(50) DEFAULT NULL,
  `total_detection_ms` float DEFAULT NULL,
  `total_collection_ms` float DEFAULT NULL,
  `collected_audio_ms` float DEFAULT NULL,
  `time_to_decision_ms` float DEFAULT NULL,
  `last_silence_ms` float DEFAULT NULL,
  `nr_ms` float DEFAULT NULL,
  `fa_ms` float DEFAULT NULL,
  `asr_trans_ms` float DEFAULT NULL,
  `asr_seg_ms` float DEFAULT NULL,
  `latency_ms` float DEFAULT NULL,
  `human_score` int(9) DEFAULT NULL,
  `machine_score` int(9) DEFAULT NULL,
  `dc_score` int(9) DEFAULT NULL,
  `con_num` smallint(6) DEFAULT NULL,
  `asr_confidence` float DEFAULT NULL,
  `asr_comp_ratio` float DEFAULT NULL,
  `rec_url` varchar(255) DEFAULT NULL,
  `vca_server_host` varchar(100) DEFAULT NULL,
  `vca_server_port` smallint(5) unsigned DEFAULT 0,
  `gpu_id` smallint(6) DEFAULT NULL,
  `voice_sig_id` varchar(36) DEFAULT '',
  `voice_min_dist` float DEFAULT 0,
  `voice_match_ms` float DEFAULT 0,
  `audio_sig_id` varchar(36) DEFAULT '',
  `audio_min_dist` float DEFAULT 0,
  `audio_match_ms` float DEFAULT 0,
  `sig_match_type` varchar(10) DEFAULT '',
  PRIMARY KEY (`vca_log_id`),
  KEY `channel` (`channel`),
  KEY `analysis_date` (`analysis_date`),
  KEY `call_id` (`call_id`),
  KEY `amd_status` (`amd_status`),
  KEY `amd_cause` (`amd_cause`),
  KEY `text` (`text`)
) ENGINE=MyISAM;

CREATE TABLE vicidial_vca_log_archive LIKE vicidial_vca_log;
ALTER TABLE vicidial_vca_log_archive MODIFY vca_log_id INT(9) UNSIGNED NOT NULL;

INSERT INTO `vicidial_music_on_hold` VALUES ('vca-moh','VCA AMD background silence','Y','N','N','---ALL---');
INSERT INTO `vicidial_music_on_hold_files` VALUES ('6s-silence','vca-moh',1);

INSERT INTO vicidial_settings_containers(container_id,container_notes,container_type,user_group,container_entry,modify_stamp) VALUES('Default_ViciAMD_status_map','Default ViciAMD status map','AMD_STATUS_MAP','---ALL---','CALLSCREEN,* => VAMCS\nCALLSCREEN,PATTERN => VAMCS\nCALLSCREEN,SIGNATURE => VAMCS\nFAS,* => VAMFAS\nFAS,INITIALSILENCE => VAMFIS\nFAS,RINGING => VAMRNG\nFAS,SIGNATURE => VAMFAS\nFAX,* => VAMFAX\nFAX,ANS_SIG => VAMFAX\nFAX,CNG_SIG => VAMFAX\nHUMAN,* => VAMMAN\nHUMAN,HUMAN => VAMMAN\nINTERCEPT,* => VAMSIT\nINTERCEPT,SIGNATURE => VAMSIT\nINTERCEPT,SITTONES => VAMSIT\nMACHINE,* => VAMMAC\nMACHINE,MAXWORDS => VAMMAC\nMACHINE,PATTERN => VAMMAC\nMACHINE,SIGNATURE => VAMMAC\nNOTSURE,* => VAMNS\nNOTSURE,HANGUP => VAMNS\nNOTSURE,HIGHCOMPRESS => VAMNS\nNOTSURE,LOWCONFIDENCE => VAMNS\nNOTSURE,LOWSCORE => VAMNS\nNOTSURE,NOTHUMAN => VAMNS\nNOTSURE,NOTSURE => VAMNS',NOW());
