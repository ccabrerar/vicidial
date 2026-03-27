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
  PRIMARY KEY (`vca_log_id`),
  KEY `channel` (`channel`),
  KEY `analysis_date` (`analysis_date`),
  KEY `call_id` (`call_id`),
  KEY `amd_status` (`amd_status`),
  KEY `amd_cause` (`amd_cause`),
  KEY `text` (`text`)
) ENGINE=MyISAM AUTO_INCREMENT=164460 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE vicidial_vca_log_archive LIKE vicidial_vca_log;
ALTER TABLE vicidial_vca_log_archive MODIFY vca_log_id INT(9) UNSIGNED NOT NULL;

ALTER TABLE vicidial_vca_log ADD sig_id VARCHAR(36) DEFAULT '', ADD
sig_min_dist FLOAT DEFAULT 0, ADD sig_match_ms FLOAT DEFAULT 0;
ALTER TABLE vicidial_vca_log_archive ADD sig_id VARCHAR(36) DEFAULT '',
ADD sig_min_dist FLOAT DEFAULT 0, ADD sig_match_ms FLOAT DEFAULT 0;

ALTER TABLE vicidial_vca_log RENAME column sig_id to voice_sig_id
ALTER TABLE vicidial_vca_log RENAME column sig_min_dist to voice_min_dist;
ALTER TABLE vicidial_vca_log RENAME column sig_match_ms to voice_match_ms;
ALTER TABLE vicidial_vca_log_archive RENAME column sig_id to voice_sig_id
ALTER TABLE vicidial_vca_log_archive RENAME column sig_match_ms to voice_match_ms;
ALTER TABLE vicidial_vca_log_archive RENAME column sig_min_dist to voice_min_dist;
ALTER TABLE vicidial_vca_log ADD column audio_sig_id VARCHAR(36) default '';
ALTER TABLE vicidial_vca_log ADD column audio_min_dist FLOAT default 0;
ALTER TABLE vicidial_vca_log ADD column audio_match_ms FLOAT default 0;
ALTER TABLE vicidial_vca_log ADD column sig_match_type VARCHAR(10) default '';
ALTER TABLE vicidial_vca_log_archive ADD column audio_sig_id VARCHAR(36) default '';
ALTER TABLE vicidial_vca_log_archive ADD column audio_min_dist FLOAT default 0;
ALTER TABLE vicidial_vca_log_archive ADD column audio_match_ms FLOAT default 0;
ALTER TABLE vicidial_vca_log_archive ADD column sig_match_type VARCHAR(10) default '';
