<?php

require_once('InspectionShell.php');

function private_scope_test() {
    $hidden_var = range('a', 'z');
    new InspectionShell(get_defined_vars());
}

new InspectionShell();
