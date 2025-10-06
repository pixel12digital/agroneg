<?php
/**
 * Script para verificar e corrigir configuração de branch
 * Execute este arquivo para diagnosticar problemas de deploy
 */

header('Content-Type: application/json; charset=utf-8');

$diagnostico = [
    'timestamp' => date('Y-m-d H:i:s'),
    'repository' => 'https://github.com/pixel12digital/agroneg.git',
    'issues' => [],
    'solutions' => []
];

// Verificar branch atual local
$git_branch_output = [];
exec('git branch --show-current 2>&1', $git_branch_output);
$current_branch = trim(implode('', $git_branch_output));

$diagnostico['local_branch'] = $current_branch;

// Verificar branches remotos
$git_remote_branches = [];
exec('git branch -r 2>&1', $git_remote_branches);
$diagnostico['remote_branches'] = $git_remote_branches;

// Verificar se existe branch master
$has_master = false;
$has_main = false;

foreach ($git_remote_branches as $branch) {
    if (strpos($branch, 'origin/master') !== false) {
        $has_master = true;
    }
    if (strpos($branch, 'origin/main') !== false) {
        $has_main = true;
    }
}

$diagnostico['branch_analysis'] = [
    'has_master' => $has_master,
    'has_main' => $has_main,
    'current_branch' => $current_branch
];

// Diagnóstico do problema
if (!$has_master && $has_main) {
    $diagnostico['issues'][] = "Hostinger está tentando clonar branch 'master' que não existe";
    $diagnostico['solutions'][] = "Configurar Hostinger para usar branch 'main'";
    $diagnostico['solutions'][] = "No painel Hostinger: Advanced → Git → Branch: main";
}

if ($current_branch !== 'main') {
    $diagnostico['issues'][] = "Branch local não é 'main'";
    $diagnostico['solutions'][] = "Executar: git checkout main";
}

// Status geral
$diagnostico['status'] = empty($diagnostico['issues']) ? 'OK' : 'NEEDS_FIX';

echo json_encode($diagnostico, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
