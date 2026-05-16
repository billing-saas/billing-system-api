<?php
$text = file_get_contents('tests/Feature/UserProfileTest.php');
var_dump(strpos($text, 'public function test_store_cree_un_profil_avec_succes()'));
