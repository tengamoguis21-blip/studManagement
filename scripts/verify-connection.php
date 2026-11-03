<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$db = config('database.connections.pgsql');
$process = new Symfony\Component\Process\Process(
    ['psql', '--no-psqlrc', '--command=\\conninfo'],
    null,
    ['PGHOST' => $db['host'], 'PGPORT' => (string) $db['port'],
        'PGDATABASE' => $db['database'], 'PGUSER' => $db['username'],
        'PGPASSWORD' => $db['password'], 'PGSSLMODE' => 'require'],
);
$process->setTimeout(30);
$process->run();
echo $process->getOutput();
fwrite(STDERR, $process->getErrorOutput());
exit($process->getExitCode());