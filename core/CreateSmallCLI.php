<?php

namespace App\Core;

class CreateSmallCLI
{
    private bool $runningStatus = true;

    public function run()
    {
        while ($this->runningStatus == true) {

            echo "Mini CLI Tool\n\n";
            echo "Choose an option:\n";
            echo "1. Edit .env file\n";
            echo "2. Create a new Controller file\n";
            echo "3. Create a new Model file\n";
            echo "4. Exit\n";
            echo "Choice: ";
            $choice = trim(fgets(STDIN));

            match ($choice) {
                '1' => $this->editEnv(),
                '2' => $this->createController(),
                '3' => $this->createModels(),
                '4' => $this->runningStatus = false,
                default => print("Invalid option\n"),
            };
        }
    }
    private function editEnv()
    {
        $envPath = __DIR__ . '/../.env';
        if (!file_exists($envPath)) {
            fopen($envPath, 'w');
        }
        //clear file
        file_put_contents($envPath, "");
        while (true) {
            echo "Type EXIT to left this menu!\n";
            echo "Type the key to be set:";
            $key = trim(fgets(STDIN));
            if ($key == "EXIT") {
                return;
            }
            echo "New value: ";
            $value = trim(fgets(STDIN));

            $lines = file($envPath);
            $found = false;

            foreach ($lines as &$line) {
                if (str_starts_with($line, "$key=")) {
                    $line = "$key='$value'\n";
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $lines[] = "$key='$value'\n";
            }

            file_put_contents($envPath, implode('', $lines));
            echo ".env updated.\n";
        }
    }
    private function createController()
    {
        echo "Controller name: ";
        $name = trim(fgets(STDIN));
        $className = ucfirst($name);
        $filePath = __DIR__ . "/../controller/{$className}.php";

        if (file_exists($filePath)) {
            echo "File already exists.\n";
            return;
        }

        $template = <<<PHP
        <?php

        namespace App\Controller;

        use App\Core\BaseController;

        class $className extends BaseController
        {
            
        }
        PHP;

        file_put_contents($filePath, $template);
        echo "Controller created at: $filePath\n";
    }
    private function createModels()
    {
        echo "Model name: ";
        $name = trim(fgets(STDIN));
        $className = ucfirst($name);
        $filePath = __DIR__ . "/../model/{$className}.php";

        if (file_exists($filePath)) {
            echo "File already exists.\n";
            return;
        }

        $properties = [];

        while (true) {
            echo "Type EXIT to finish creating the model.\n";

            echo "Property name: ";
            $propName = trim(fgets(STDIN));
            if (strtoupper($propName) === 'EXIT') {
                break;
            }

            echo "Property type (int, string, bool, float): ";
            $type = trim(fgets(STDIN));

            $nullable = false;
            echo "Should this property be nullable? (yes/no): ";
            $nullableInput = strtolower(trim(fgets(STDIN)));
            if ($nullableInput === 'yes') {
                $nullable = true;
            }

            $properties[] = [
                'name' => $propName,
                'type' => $type,
                'nullable' => $nullable
            ];
        }

        // Build class content
        $propsCode = "";
        $constructorParams = [];
        $constructorBody = [];
        $gettersSetters = [];

        foreach ($properties as $prop) {
            $name = $prop['name'];
            $type = $prop['type'];
            $nullable = $prop['nullable'];

            $phpType = ($nullable ? "?" : "") . $type;

            // Property
            $propsCode .= "    private {$phpType} \${$name};\n";

            // Constructor
            $default = $nullable ? "null" : ($type === 'string' ? "''" : "0");
            $constructorParams[] = "{$phpType} \${$name} = {$default}";
            $constructorBody[] = "        \$this->{$name} = \${$name};";

            // Getter
            $getterReturnType = $phpType;
            $getter = <<<PHP

            public function get{$this->camel($name)}(): {$getterReturnType}
            {
                return \$this->{$name};
            }
        PHP;

            // Setter
            $setter = <<<PHP

            public function set{$this->camel($name)}({$phpType} \${$name}): void
            {
                \$this->{$name} = \${$name};
            }
        PHP;

            $gettersSetters[] = $getter;
            $gettersSetters[] = $setter;
        }

        $constructorCode = implode(', ', $constructorParams);
        $constructorBodyCode = implode("\n", $constructorBody);
        $methodsCode = implode("\n", $gettersSetters);

        $template = <<<PHP
        <?php

        namespace App\Model;

        use App\Core\BaseModel;

        class {$className} extends BaseModel
        {
        {$propsCode}
            public function __construct({$constructorCode})
            {
        {$constructorBodyCode}
            }
        {$methodsCode}
        }
        PHP;

        file_put_contents($filePath, $template);
        echo "✅ Model created at: $filePath\n";
    }

    private function camel($str): string
    {
        return ucfirst($str);
    }
}
