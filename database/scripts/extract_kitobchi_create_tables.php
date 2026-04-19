<?php

/**
 * kitobchi.sql dan faqat CREATE TABLE bloklarini ajratib,
 * database/schema/kitobchi_structure.sql faylini generatsiya qiladi.
 *
 * Ishlatish: php database/scripts/extract_kitobchi_create_tables.php
 */

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$src = $root.'/kitobchi.sql';
$dst = dirname(__DIR__).'/schema/kitobchi_structure.sql';

if (! is_readable($src)) {
    fwrite(STDERR, "Topilmadi yoki o‘qib bo‘lmaydi: {$src}\n");
    exit(1);
}

$dir = dirname($dst);
if (! is_dir($dir)) {
    mkdir($dir, 0755, true);
}

$in = false;
$skip = false;
$buffer = [];

$fh = fopen($src, 'rb');
$out = fopen($dst, 'wb');

fwrite($out, "-- Avtogeneratsiya: kitobchi.sql dan CREATE TABLE (INSERT yo‘q)\n");
fwrite($out, "/*!40101 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;\n");
fwrite($out, "SET NAMES utf8mb4;\n\n");

while (($line = fgets($fh)) !== false) {
    if (! $in && str_starts_with($line, 'CREATE TABLE `')) {
        if (str_starts_with($line, 'CREATE TABLE `migrations`')
            || preg_match('/^CREATE TABLE `moonshine_/i', $line) === 1) {
            $skip = true;
            $in = true;
            $buffer = [$line];

            continue;
        }
        $in = true;
        $skip = false;
        $buffer = [$line];

        continue;
    }

    if ($in) {
        $buffer[] = $line;
        if (preg_match('/^\) ENGINE=/m', $line)) {
            if (! $skip) {
                $sql = implode('', $buffer);
                $sql = preg_replace('/AUTO_INCREMENT=\d+/i', '', $sql);
                $sql = preg_replace('/^CREATE TABLE `/m', 'CREATE TABLE IF NOT EXISTS `', $sql);
                fwrite($out, $sql."\n\n");
            }
            $in = false;
            $skip = false;
            $buffer = [];
        }
    }
}

fclose($fh);

fwrite($out, "/*!40101 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;\n");
fclose($out);

echo "Yozildi: {$dst}\n";
