<?php

use App\Core\YamlParser as Yaml;

class YamlTest extends \PHPUnit\Framework\TestCase
{
    private $validYamlPath;
    private $invalidYamlPath;

    private $backupYamlPath;
    private $validYamlContent = <<<YAML
info:
  path: /phpInfo
  controller: App\Controller\NewPhpRouteImp
  action: phpInfo

test_yaml_parameter:
  path: /yamlparam/{param}
  controller: App\Controller\NewPhpRouteImp
  action: yamlParam
YAML;

    protected function setUp(): void
    {
        // Create a temporary valid YAML file
        $this->validYamlPath = dirname(__DIR__) . '/../config/routes.yaml';

        // Backup the original file (if it exists)
        if (file_exists($this->validYamlPath)) {
            $this->backupYamlPath = $this->validYamlPath . '.bak';
            copy($this->validYamlPath, $this->backupYamlPath);
        }
        file_put_contents($this->validYamlPath, $this->validYamlContent);
        // Path to a non-existent file
        $this->invalidYamlPath = sys_get_temp_dir() . '/non_existent.yaml';
    }

    protected function tearDown(): void
    {
        // Restore the original file if it was backed up
        if (isset($this->backupYamlPath) && file_exists($this->backupYamlPath)) {
            rename($this->backupYamlPath, $this->validYamlPath);
        } else {
            // If no backup, just remove the test file
            unlink($this->validYamlPath);
        }
    }

    public function testParseValidYamlFile()
    {
        $parsedData = Yaml::parseFile($this->validYamlPath);
        $this->assertIsArray($parsedData);
        $this->assertArrayHasKey('info', $parsedData);
        $this->assertEquals('/phpInfo', $parsedData['info']['path']);
        $this->assertEquals('App\Controller\NewPhpRouteImp', $parsedData['info']['controller']);
        $this->assertEquals('phpInfo', $parsedData['info']['action']);

        $this->assertArrayHasKey('test_yaml_parameter', $parsedData);
        $this->assertEquals('/yamlparam/{param}', $parsedData['test_yaml_parameter']['path']);
        $this->assertEquals('App\Controller\NewPhpRouteImp', $parsedData['test_yaml_parameter']['controller']);
        $this->assertEquals('yamlParam', $parsedData['test_yaml_parameter']['action']);
    }

    public function testParseFileThrowsExceptionForMissingFile()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Error 404: File config/routes.yaml does not exist");
        Yaml::parseFile($this->invalidYamlPath);
    }
}
