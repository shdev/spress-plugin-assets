<?php

use Yosymfony\Spress\Core\IO\IOInterface;
use Yosymfony\Spress\Core\Plugin\PluginInterface;
use Yosymfony\Spress\Core\Plugin\EventSubscriber;
use Yosymfony\Spress\Core\Plugin\Event\EnvironmentEvent;
use Yosymfony\Spress\Core\Plugin\Event\FinishEvent;
use Yosymfony\Spress\Core\Support\AttributesResolver;
use Symfony\Component\Filesystem\Filesystem;

class SpressAssets implements PluginInterface
{
    const PATH_TO_ROOT = '../../../../';
    /** string[] */
    const IMAGE_EXTENSIONS = ['jpg', 'png', 'jpeg', 'gif'];

    /** string */
    const BASE_PATH = __DIR__ . '/../../..';

    /** string */
    const DEFAULT_ASSET_OUTPUT_PATH = 'build/assets';

    /** string */
    const CACHE_PATH = '.cache/assets';

    /** string */
    const DEFAULT_ASSET_OUTPUT_WEB_PATH = '/assets';
    /** string */
    const ASSET_PATH = self::DEFAULT_ASSET_OUTPUT_WEB_PATH;

    /** @var array */
    private $files = [];

    /** @var string[]  */
    private $hashes = [];

    /** @var IOInterface */
    private $io;

    /** @var Filesystem */
    private $fs;

    /** @var array */
    private $config;

    /** @var AttributesResolver */
    private $fileResolver;

    /** @var AttributesResolver */
    private $configurationResolver;

    /**
     * @param array $attributes
     * @param string $keyPrefix
     * @return string
     */
    public static function htmlAttributes(array $attributes, $keyPrefix = '')
    {
        $s = '';
        foreach ($attributes as $k => $v) {
            if (is_numeric($k)) {
                $s .= ' ';
                $s .= htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
            } else {
                if (null !== $v) {
                    $s .= ' ';
                    $s .= $keyPrefix . htmlspecialchars($k, ENT_QUOTES, 'UTF-8') . '="' . htmlspecialchars($v, ENT_QUOTES, 'UTF-8') . '"';
                }
            }
        }
        return $s;
    }

    /**
     * @param EventSubscriber $subscriber
     */
    public function initialize(EventSubscriber $subscriber)
    {
        $subscriber->addEventListener('spress.start', 'onStart');
        $subscriber->addEventListener('spress.finish', 'onFinish');

        $this->fs = new Filesystem();
    }

    /**
     * @return array
     */
    public function getMetas()
    {
        return [
            'name' => 'shdev/spress-plugin-assets',
            'description' => 'It allows to asset boost your Spress site',
            'author' => 'Sebastian Holtz',
            'license' => 'MIT',
        ];
    }

    /**
     * @param EnvironmentEvent $event
     * @throws \Exception
     */
    public function onStart(EnvironmentEvent $event)
    {
        $this->io = $event->getIO();

        $this->config = $this->getConfigurationResolver()->resolve($event->getConfigValues());
        $event->setConfigValues($this->config);

        /** @var \Yosymfony\Spress\Core\DataSource\DataSourceManager $dsm */
        $dsm = $event->getDataSourceManager();

        $dsm->getItems();

        $this->io->write(count($dsm->getItems()));

        /** @var \Yosymfony\Spress\Core\ContentManager\Renderizer\TwigRenderizer $renderizer */
        $renderizer = $event->getRenderizer();

        $renderizer->addTwigFilter('asset_path', function($filePath, $options = []) use ($event) {
            return $this->getAssetUrl($event, $filePath, $options);
        });

        $renderizer->addTwigFilter('img', function($filePath, $options = []) use ($event) {
            $options += ['attr' => []];
            $options['attr'] += ['src' => $this->getAssetUrl($event, $filePath, $options)];
            return '<img' . self::htmlAttributes($options['attr']). '/>';
        }, ['is_safe' => ['html']] );

        $renderizer->addTwigFilter('css', function($filePath, $options = []) use ($event) {
            $options += ['attr' => []];
            $options['attr'] += [
                'rel' => 'stylesheet',
                'href' => $this->getAssetUrl($event, $filePath, $options)
            ];
            return '<link' . self::htmlAttributes($options['attr']). '/>';
        }, ['is_safe' => ['html']] );

        $renderizer->addTwigFilter('js', function($filePath, $options = []) use ($event) {
            $options += ['attr' => []];
            $options['attr'] += ['src' => $this->getAssetUrl($event, $filePath, $options)];
            return '<script'. self::htmlAttributes($options['attr']) . '></script>';
        }, ['is_safe' => ['html']] );

    }

    /**
     * @param EnvironmentEvent $event
     * @param $filePath
     * @param array $options
     * @return string
     * @throws Exception
     */
    private function getAssetUrl(EnvironmentEvent $event, $filePath, $options = []) {
        $fullFilepath = self::BASE_PATH . self::ASSET_PATH . '/' . $filePath;

        if (!$this->fs->exists($fullFilepath) )
        {
            $fullFilepath = self::BASE_PATH . '/themes/' . $event->getConfigValues()['themes']['name'] . '/src' . self::ASSET_PATH . '/' . $filePath;
            /** @noinspection NotOptimalIfConditionsInspection */
            if (!$this->fs->exists($fullFilepath) )
            {
                throw new \RuntimeException('Can\'t find '. $fullFilepath);
            }
        }

        if(!isset($this->hashes[$fullFilepath])) {
            $this->hashes[$fullFilepath] = md5_file($fullFilepath);
        }

        $options = $this->getFileResolver()->resolve($options);

        $hashOptions = [
            'resize' => $options['resize'],
            'crop' => $options['crop'],
            'gravity' => $options['gravity'],
            'quality' => $options['quality'],
        ];
        ksort($hashOptions);

        $optionsHash = md5(json_encode($hashOptions));

        $fileHash = md5($this->hashes[$fullFilepath] . '_' . $optionsHash);

        $this->files[$fullFilepath . '_' . $fileHash] = [
            'file' => $filePath,
            'fullFilePath' => $fullFilepath,
            'baseFileHash' => $this->hashes[$fullFilepath],
            'hash' => $fileHash,
            'options' => $options,
        ];

        list($fileManager, $newFilePath) = $this->getFilename($this->files[$fullFilepath . '_' . $fileHash]);

        $assetOutputWebPrefix = isset($this->config['asset_output_web_prefix'])?
            $this->config['asset_output_web_prefix']: self::DEFAULT_ASSET_OUTPUT_WEB_PATH;

        return $assetOutputWebPrefix . '/' . $newFilePath;
    }

    /**
     * @param FinishEvent $event
     * @throws \Symfony\Component\Filesystem\Exception\FileNotFoundException
     * @throws \Symfony\Component\Filesystem\Exception\IOException
     */
    public function onFinish(FinishEvent $event)
    {
        $configAssetOutputPath = isset($this->config['asset_output_path'])?
            $this->config['asset_output_path']:self::DEFAULT_ASSET_OUTPUT_PATH;

        $assetOutputPath = self::PATH_TO_ROOT . $configAssetOutputPath;
        $assetCachePath = self::PATH_TO_ROOT . self::CACHE_PATH;

        $this->fs->mkdir(__DIR__ . '/' . $assetOutputPath);
        $this->io->write("Start writing assets", true, IOInterface::VERBOSITY_VERBOSE);
        foreach ($this->files as $key => $file) {

            /** @var \SplFileInfo $fileManager */
            list($fileManager, $newFilePath) = $this->getFilename($file);

            $this->fs->mkdir(__DIR__ . '/' . $assetOutputPath . '/' . $fileManager->getPath() );
            $this->io->write('src/assets/' . $file['file'] . ' => ' .
                $configAssetOutputPath . '/' . $newFilePath, true, IOInterface::VERBOSITY_VERBOSE);

            $cacheFilePath = __DIR__ . '/' . $assetCachePath . '/' . $newFilePath;
            $outputFilePath = __DIR__ . '/' . $assetOutputPath . '/' . $newFilePath;

            if (!$this->fs->exists($cacheFilePath)) {
                $this->fs->mkdir(__DIR__ . '/' . $assetCachePath . '/' . $fileManager->getPath() );
                /** @noinspection NotOptimalIfConditionsInspection */
                if (
                    in_array(strtolower($fileManager->getExtension()), self::IMAGE_EXTENSIONS, true) &&
                    (
                        (!empty($file['options']['resize'])) ||
                        (!empty($file['options']['crop'])) ||
                        (!empty($file['options']['quality']))
                    )
                ) {
                    $file = $this->imageManipultaion($file, $cacheFilePath);
                } else {
                    try {
                        $this->fs->copy(
                            $file['fullFilePath'], $cacheFilePath);
                    } catch (Exception $e) {
                        $this->io->write(sprintf('Error: can\'t copy file \'%s\'', $file['file']));
                    }
                }
            }

            try {
                $this->fs->copy($cacheFilePath, $outputFilePath);
            } catch (Exception $e) {
                $this->io->write(sprintf('Error: can\'t copy file \'%s\'', $file['file']));
            }

        }
        $this->io->write("Finished writing assets", true, IOInterface::VERBOSITY_VERBOSE);
    }

    /**
     * @param $file
     * @return array
     * @throws \Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException
     */
    private function getFilename($file)
    {
        $fileManager = new \Symfony\Component\HttpFoundation\File\File($file['file'], false);

        $filename = $fileManager->getBasename();
        if (strlen($fileManager->getExtension()) > 0) {
            $filename = substr($filename, 0, -strlen($fileManager->getExtension()) - 1);
            $filename .= '_' . $file['hash'] . '.' . $fileManager->getExtension();
        } else {
            $filename .= '_' . $file['hash'];
        }

        $newFilePath = $fileManager->getPath() ? $fileManager->getPath() . '/' . $filename : $filename;

        return [$fileManager, $newFilePath];
    }

    /**
     * @param $file
     * @param $cacheFilePath
     * @return mixed
     */
    private function imageManipultaion($file, $cacheFilePath)
    {
        $args = [escapeshellarg($file['fullFilePath']),];

        if (!empty($file['options']['resize'])) {
            $args[] = '-resize';
            $args[] = escapeshellarg($file['options']['resize']);
        }

        if (!empty($file['options']['crop'])) {
            if (!empty($file['options']['gravity'])) {
                $args[] = '-gravity';
                $args[] = escapeshellarg($file['options']['gravity']);
            }

            $args[] = '-crop';
            $args[] = escapeshellarg($file['options']['crop']);

            $args[] = '+repage';
        }

        if (!empty($file['options']['quality'])) {
            $args[] = '-quality';
            $args[] = escapeshellarg((int)$file['options']['quality']);
        }

        $args[] = escapeshellarg($cacheFilePath);

        $command = 'convert ' . implode(' ', $args);

        exec($command, $output, $statusCode);

        if (0 !== $statusCode) {
            $this->io->write(sprintf('Error while converting image \'%s\'', $file['file']));
            $this->io->write($command, true, IOInterface::VERBOSITY_DEBUG);
        }

        return $file;
    }

    private function getConfigurationResolver()
    {
        if (!$this->configurationResolver) {
            $resolver = new AttributesResolver();
            $resolver->setDefault('asset_output_web_prefix', self::DEFAULT_ASSET_OUTPUT_WEB_PATH, 'string');
            $resolver->setDefault('asset_output_path', self::DEFAULT_ASSET_OUTPUT_PATH, 'string');
            $resolver->setDefault('asset_defaults_gravity', 'Center', 'string');
            $resolver->setDefault('asset_defaults_quality', null, 'integer', false, true);
            $this->configurationResolver = $resolver;
        }
        return $this->configurationResolver;
    }

    private function getFileResolver()
    {
        if (!$this->fileResolver) {
            if (!$this->config) {
                throw new \LogicException('Config must be resolved first.');
            }

            $resolver = new AttributesResolver();
            $resolver->setDefault('resize', null, 'string', false, true);
            $resolver->setDefault('crop', null, 'string', false, true);
            $resolver->setDefault('gravity', $this->config['asset_defaults_gravity'], 'string');
            $resolver->setDefault('quality', $this->config['asset_defaults_quality'], 'integer', false, true);
            $this->fileResolver = $resolver;
        }

        return $this->fileResolver;
    }
}
