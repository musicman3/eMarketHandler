<?php

/* =-=-=-= Copyright © 2018 eMarket =-=-=-=  
  |    GNU GENERAL PUBLIC LICENSE v.3.0    |
  |  https://github.com/musicman3/eMarket  |
  =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

$download = gitHubData();

if ($download !== FALSE) {
    $file = 'https://github.com/musicman3/eMarket/archive/refs/tags/' . $download . '.zip';
    $file_name = basename($file);
    file_put_contents(getenv('DOCUMENT_ROOT') . '/' . $file_name, file_get_contents($file));
    $zip = new ZipArchive;
    $res = $zip->open(getenv('DOCUMENT_ROOT') . '/' . $file_name);
    if ($res === TRUE) {
        $zip->extractTo('.');
        $zip->close();
    } else {
        echo 'An error has occurred. Please check the permissions for the root directory.';
    }

    // Copy files
    $sourceDir = glob("eMarket*")[0];
    $destDir = getenv('DOCUMENT_ROOT');
    if (!file_exists($destDir)) {
        mkdir($destDir, 0755, true);
    }
    $dirIterator = new RecursiveDirectoryIterator($sourceDir, RecursiveDirectoryIterator::SKIP_DOTS);
    $iterator = new RecursiveIteratorIterator($dirIterator, RecursiveIteratorIterator::SELF_FIRST);
    foreach ($iterator as $object) {
        $destPath = $destDir . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
        ($object->isDir()) ? mkdir($destPath) : copy($object, $destPath);
    }

    // Delete files
    filesRemoving($sourceDir);
    filesRemoving($file_name);
    filesRemoving($destDir . '/install.php');
    // redirect
    header('Location: controller/install/');
    
} else {
    echo 'No data received from GitHub. Please refresh the page to repeat the installation procedure.';
}

/**
 * Files removing
 *
 * @param string $path Path
 * @return bool
 */
function filesRemoving($path) {
    if (is_file($path)) {
        return unlink($path);
    }
    if (is_dir($path)) {
        foreach (scandir($path) as $p) {
            if (($p != '.') && ($p != '..')) {
                filesRemoving($path . DIRECTORY_SEPARATOR . $p);
            }
        }
        return rmdir($path);
    }
    return false;
}

/**
 * GitHub Data
 *
 * @return array GitHub latest release data
 */
function gitHubData() {
    $connect = curl_init();
    curl_setopt($connect, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($connect, CURLOPT_HTTPHEADER, ['User-Agent: eMarket']);
    curl_setopt($connect, CURLOPT_URL, 'https://api.github.com/repos/musicman3/eMarket/releases/latest');
    $response_string = curl_exec($connect);
    curl_close($connect);
    if (!empty($response_string)) {
        $response = json_decode($response_string, 1);
        if (isset($response['tag_name'])) {
            return $response['tag_name'];
        }
    } else {
        return FALSE;
    }
}
