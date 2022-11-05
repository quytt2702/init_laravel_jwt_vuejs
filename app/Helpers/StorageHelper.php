<?php

namespace App\Helpers;

use Aws\CommandPool;
use Aws\Exception\AwsException;
use Aws\Exception\MultipartUploadException;
use Aws\S3\Exception\S3Exception;
use Aws\S3\MultipartUploader;
use Aws\S3\ObjectUploader;
use Aws\S3\S3Client;
use Illuminate\Contracts\Filesystem\Cloud;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Constraint;
use Intervention\Image\Facades\Image;

/**
 * Class StorageHelper
 *
 * @package App\Services
 */
class StorageHelper
{
    /**
     * @var string
     */
    protected $disk;

    /**
     * @var array
     */
    protected $config;

    public function __construct()
    {
        $this->setDisk(config('filesystems.default'));
    }

    /**
     * Get Filesystem instance
     *
     * @return Cloud|FilesystemAdapter
     */
    public function filesystem()
    {
        return Storage::disk($this->disk);
    }

    /**
     * Get current disk
     *
     * @return \Illuminate\Config\Repository|mixed
     */
    public function getDisk()
    {
        return $this->disk;
    }

    /**
     * Set disk
     *
     * @param $disk
     *
     * @return $this
     */
    public function setDisk($disk)
    {
        $this->disk   = $disk;
        $this->config = config('filesystems.disks.' . $disk, []);

        return $this;
    }

    /**
     * @param null $key
     * @param null $default
     *
     * @return array|string
     */
    public function getConfig($key = null, $default = null)
    {
        if (!is_null($key)) {
            return Arr::get($this->config, $key, $default);
        }

        return $this->config;
    }

    /**
     * Check disk is s3
     *
     * @return bool
     */
    public function isDiskS3()
    {
        return in_array($this->getDisk(), [
            's3', 'minio-s3',
        ]);
    }

    /**
     * Check folder is public
     *
     * @return bool
     */
    public function isPublicS3($path)
    {
        return Str::startsWith($path, 'public/');
    }

    /**
     * Upload image resize
     *
     * @param mixed  $file
     * @param string $path
     * @param mixed  $width
     * @param mixed  $height
     * @param string $nameFile
     *
     * @return string|null
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function uploadImageWithSize($file, $path, $width, $height, $nameFile = null)
    {
        $image = Image::make($file);
        $image = $image->resize($width, $height, function (Constraint $constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });

        if ($width > 0 && $height > 0) {
            $image = $image->crop($width, $height);
        }

        if (is_null($nameFile)) {
            $nameFile = $this->getFileNameFromFile($file);
        }

        return $this->handleFile($image->stream(), $path, $nameFile);
    }

    /**
     * Upload file and create attachment
     *
     * @param      $file
     * @param      $path
     * @param null $nameFile
     *
     * @return false|string
     */
    public function upload($file, $path, $nameFile = null)
    {
        $pathFile = $this->handleFile($file, $path, $nameFile);

        return $pathFile;
    }

    /**
     * Handle file upload is links/file
     *
     * @param      $file
     * @param      $path
     * @param null $nameFile
     *
     * @return null|string
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function handleFile($file, $path, $nameFile)
    {
        if (empty($nameFile)) {
            $nameFile = $this->getFileNameFromFile($file);
        }

        if ($file instanceof UploadedFile) {
            $content = $file->get();
        } else {
            $content = $file;
        }

        $pathFile = rtrim($path, '/') . '/' . $nameFile;

        $isUpload = $this->filesystem()->put(
            $pathFile, // Path
            $content   // Uploaded File
        );

        if (!$isUpload) {
            $pathFile = null;
        }

        return $pathFile;
    }

    /**
     * Build file name from file
     *
     * @param $file
     *
     * @return string
     */
    private function getFileNameFromFile($file)
    {
        if ($file instanceof UploadedFile) {
            return sprintf(
                '%s-%s.%s',
                Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)),
                now()->timestamp,
                $file->getClientOriginalExtension()
            );
        }

        return sprintf(
            '%s-%s.%s',
            Str::random(8),
            now()->timestamp,
            'jpg'
        );
    }

    /**
     * Delete file
     *
     * @param $pathFile
     *
     * @return bool
     */
    public function delete($pathFile)
    {
        if ($pathFile && $this->exists($pathFile)) {
            return $this->filesystem()->delete($pathFile);
        }

        return false;
    }

    /**
     * Delete directory
     *
     * @param $path
     *
     * @return bool
     */
    public function deleteDirectory($path)
    {
        if ($path && $this->exists($path)) {
            return $this->filesystem()->deleteDir($path);
        }

        return false;
    }

    /**
     * Update file and delete old file
     *
     * @param      $file
     * @param      $path
     * @param      $oldPath
     * @param null $nameFile
     *
     * @return false|string
     */
    public function update($file, $path, $oldPath, $nameFile = null)
    {
        $pathFile = $this->upload($file, $path, $nameFile);

        if ($oldPath) {
            $this->delete($oldPath);
        }

        return $pathFile;
    }

    /**
     * Upload multiple file to s3
     *
     * @param array $fileTransfers
     *
     * @return array
     * @throws \Exception
     */
    public function batchUploadFileToS3(array $fileTransfers)
    {
        if (!$this->isDiskS3()) {
            throw new \Exception('Method only supports with disk as s3');
        }

        try {
            /**@var S3Client $s3Client */
            $s3Client = $this->filesystem()->getDriver()->getAdapter()->getClient();
            $commands = collect($fileTransfers)
                ->map(function ($fileTransfer) use ($s3Client) {
                    return $s3Client->getCommand('PutObject', array_filter(
                        [
                            'Bucket'             => $this->getConfig('bucket'),
                            'Key'                => $fileTransfer['path'],
                            'Body'               => $fileTransfer['content'],
                            'ContentType'        => $fileTransfer['file_type'] ?? null,
                            'ContentDisposition' => 'attachment',
                        ]
                    ));
                });

            return CommandPool::batch($s3Client, $commands);
        } catch (AwsException $ex) {
            throw $ex;
        }
    }

    /**
     * Delete multiple file in s3
     *
     * @param array $filePaths
     *
     * @return array
     * @throws \Exception
     */
    public function batchDeleteFileInS3(array $filePaths)
    {
        if (!$this->isDiskS3()) {
            throw new \Exception('Method only supports with disk as s3');
        }

        try {
            /**@var S3Client $s3Client */
            $s3Client = $this->filesystem()->getDriver()->getAdapter()->getClient();

            $commands = collect($filePaths)
                ->map(function ($filePath) use ($s3Client) {
                    return $s3Client->getCommand('deleteObject', [
                        'Bucket' => $this->getConfig('bucket'),
                        'Key'    => $filePath,
                    ]);
                });

            return CommandPool::batch($s3Client, $commands);
        } catch (AwsException $ex) {
            throw $ex;
        }
    }

    /**
     * Copy multiple file in s3
     *
     * @param array $filePaths
     *
     * @return array
     * @throws \Exception
     */
    public function batchCopyFileInS3(array $filePaths, string $folder)
    {
        if (!$this->isDiskS3()) {
            throw new \Exception('Method only supports with disk as s3');
        }

        try {
            /**@var S3Client $s3Client */
            $s3Client = $this->filesystem()->getDriver()->getAdapter()->getClient();
            $paths    = [];
            $bucket   = config('filesystems.disks.s3.bucket');
            $commands = collect($filePaths)
                ->map(function ($fromFile) use ($folder, &$paths, $bucket, $s3Client) {
                    $name   = basename($fromFile);
                    $toPath = $folder . "/$name";

                    $paths[] = $toPath;

                    return $s3Client->getCommand('CopyObject', [
                        'Bucket'            => $bucket,
                        'MetadataDirective' => 'COPY',
                        'Key'               => $toPath,
                        'CopySource'        => $bucket . '/' . $fromFile,
                    ]);
                });

            $batchResults = CommandPool::batch($s3Client, $commands);

            // Remove path invalid
            foreach ($batchResults as $index => $result) {
                if ($result instanceof \Throwable) {
                    unset($paths[$index]);
                }
            }

            return [$paths, $batchResults];
        } catch (AwsException $ex) {
            throw $ex;
        }
    }

    /**
     * Check multiple file exists in s3
     *
     * @param array $filePaths
     *
     * @return array
     * @throws \Exception
     */
    public function batchCheckFileExistInS3(array $filePaths)
    {
        $exists    = [];
        $filePaths = array_filter($filePaths);
        if (!$this->isDiskS3()) {
            throw new \Exception('Method only supports with disk as s3');
        }

        if (empty($filePaths)) {
            return $exists;
        }

        try {
            /**@var S3Client $s3Client */
            $s3Client = $this->filesystem()->getDriver()->getAdapter()->getClient();
            $commands = collect($filePaths)
                ->map(function ($filePath) use ($s3Client) {
                    return $s3Client->getCommand('HeadObject', array_filter(
                        [
                            'Bucket' => $this->getConfig('bucket'),
                            'Key'    => $filePath,
                        ]
                    ));
                });

            $results = CommandPool::batch($s3Client, $commands);

            // Filter file exists in s3
            foreach ($results as $index => $item) {
                if ($item instanceof S3Exception) {
                    // File exists but error permission denied
                    if ($item->getAwsErrorCode() == 'AccessDenied') {
                        $exists[] = $filePaths[$index];
                    }
                    continue;
                }

                $exists[] = $filePaths[$index];
            }

            return $exists;
        } catch (AwsException $ex) {
            throw $ex;
        }
    }

    /**
     * @param      $pathFileUpload
     * @param      $pathFileTo
     * @param bool $sync
     *
     * @return mixed
     */
    public function putOrMultipartUploaderS3($pathFileUpload, $pathFileTo, bool $sync = true)
    {
        if (!$this->isDiskS3()) {
            throw new \Exception('Method only supports with disk as s3');
        }
        /**@var S3Client $s3Client */
        $s3Client = $this->filesystem()->getDriver()->getAdapter()->getClient();

        $pathFileTo = trim($pathFileTo, '/');
        // Using stream instead of file path
        $source = fopen($pathFileUpload, 'rb');

        $uploader = new ObjectUploader(
            $s3Client,
            $this->getConfig('bucket'),
            $pathFileTo,
            $source,
            'private',
            [
                'mup_threshold' => 83886080, // 80mb
            ]
        );

        do {
            try {
                if ($sync) {
                    $result = $uploader->upload();
                } else {
                    $result = $uploader->promise();
                }
            } catch (MultipartUploadException $e) {
                rewind($source);
                $uploader = new MultipartUploader($s3Client, $source, [
                    'state' => $e->getState(),
                ]);
            }
        } while (!isset($result));

        fclose($source);

        return $pathFileTo;
    }

    /**
     * update image resize and delete old file
     *
     * @param mixed  $file
     * @param string $path
     * @param int    $width
     * @param int    $height
     * @param mixed  $oldPath
     *
     * @return string|null
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function updateImageWithSize(UploadedFile $file, $path, $width, $height, $oldPath)
    {
        $pathFile = $this->uploadImageWithSize($file, $path, $width, $height);

        if ($oldPath) {
            $this->delete($oldPath);
        }

        return $pathFile;
    }

    /**
     * @param $path
     *
     * @return bool
     */
    public function exists($path)
    {
        return $this->filesystem()->exists($path);
    }

    /**
     * Get url from path
     *
     * @param $path
     *
     * @return mixed
     */
    public function getUrl($path)
    {
        $url = null;

        if ($path) {
            if ($this->isDiskS3()) {
                $url = rtrim($this->getConfig('url'), '/') . '/' . ltrim($path, '/');
            } else {
                $url = $this->filesystem()->url($path);
            }
        }

        return $url;
    }

    /**
     * Get base from path
     *
     * @param $path
     *
     * @return mixed
     */
    public function getPath($path)
    {
        $url = null;

        if ($path) {
            $path = ltrim($path, '/');

            $url = $this->filesystem()->path($path);
        }

        return $url;
    }

    /**
     * Get content from path
     *
     * @param $path
     *
     * @return null|string
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function get($path)
    {
        $content = null;

        if ($path) {
            $content = $this->filesystem()->get($path);
        }

        return $content;
    }

    /**
     * @param $from
     * @param $to
     *
     * @return bool
     */
    public function move($from, $to)
    {
        return $this->filesystem()->move($from, $to);
    }
}
