

INSERT INTO `mod_intouch_settings` (`key`, `value`) VALUES ( 'usewysiwyg', '1' )


-- command split --


UPDATE `tbladdonmodules` SET `value` = '2.0.1' WHERE `module` = 'intouch' AND `setting` = 'version'

  