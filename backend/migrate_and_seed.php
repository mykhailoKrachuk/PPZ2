<?php
global $db;
require __DIR__.'/config.php';

$db->exec("
CREATE TABLE IF NOT EXISTS users(
  id SERIAL PRIMARY KEY,
  login TEXT UNIQUE NOT NULL,
  password_hash TEXT NOT NULL,
  role TEXT NOT NULL CHECK (role IN ('user','employee','deliver'))
);
");

$ins=$db->prepare("
  INSERT INTO users(login,password_hash,role)
  VALUES (:login,:hash,:role)
  ON CONFLICT(login) DO UPDATE
    SET password_hash=EXCLUDED.password_hash, role=EXCLUDED.role
");

$ins->execute([':login'=>'worker1', ':hash'=>password_hash('emp123',PASSWORD_BCRYPT),  ':role'=>'employee']);
$ins->execute([':login'=>'client1', ':hash'=>password_hash('user123',PASSWORD_BCRYPT), ':role'=>'user']);
$ins->execute([':login'=>'deliver1', ':hash'=>password_hash('dell123',PASSWORD_BCRYPT), ':role'=>'deliver']);

echo 'OK: migration + seed done';
