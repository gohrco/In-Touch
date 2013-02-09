

INSERT INTO `mod_intouch_settings` (`key`, `value`) VALUES ( 'fetoenable', '0' )


-- command split --


UPDATE `tbladdonmodules` SET `value` = '2.1.0' WHERE `module` = 'intouch' AND `setting` = 'version'

