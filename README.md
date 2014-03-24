Com este módulo é possível utilizar o TDD com phpunit.

Requisitos:
========
<ul>
  <li>[Zend Framework 2](http://framework.zend.com/)</li>
  <li>[Doctrine-ORM-Module](http://www.doctrine-project.org/projects/orm.html)</li>
  <li>[PhpUnit](http://phpunit.de/)</li>
</ul>


Instalação:
========
Adiciona no composer.json.
<pre>

  "juizmill/zf-test": "dev-master"
  
</pre>

Configurações:
===========

Na pasta <strong>config</strong> criar um arquivo chamado: test.config.php
Este arquivo deve conter as seguintes informações:

<pre>

    <?php
    
      return array(
      
          'modules' => array(
             'Módulos que você usará no sistema'
          ),
          'module_listener_options' => array(
              'module_paths' => array(
                  'module',
                  'vendor',
              ),
          ),
          'exclude_modules' =>array(
              'Módulos que você não queira que o TDD teste'
          ),
      );
    
</pre>

Próximo passo é criar uma pasta chamada <strong>tests</strong> dentro do módulo que queira realizar os testes, ficando desta forma:

<img src="http://webpatterns.com.br/config-directory-TDD.jpg" />

No arquivo Bootstrap.php dever ser desta forma:

<code>

    <?php
    
    namespace Usuario;
    
    //Caso use PHPUNIT da PEAR descomente esta linha.
    //require_once(getcwd() . '/../../../vendor/juizmill/zf-test/src/ZFTest/Test/AbstractBootstrap.php');
    
    use ZFTest\Test\AbstractBootstrap;
    
    error_reporting(E_ALL | E_STRICT);
    chdir(__DIR__);
    
    class Bootstrap extends AbstractBootstrap
    {
    
    }
    
    Bootstrap::init();
    
</code>

Um exemplo do phpunit.xml:

<code>

    <phpunit
        bootstrap="Bootstrap.php"
        colors="true"
        backupGlobals="false">
        <!-- Mudar os nomes do Módulo -->
        <testsuites>
            <testsuite name="USUARIO Test">
                <directory>./</directory>
            </testsuite>
        </testsuites>
        <filter>
            <whitelist>
                <directory suffix=".php">../</directory>
                <exclude>
                    <file>../Module.php</file>
                    <directory>../languageArray</directory>
                    <directory>../config</directory>
                    <directory>../tests</directory>
                </exclude>
            </whitelist>
        </filter>
        <logging>
            <log type="coverage-html" target="_reports/coverage" title="Modulo USUARIO" charset="UTF-8" yui="true" highlight="true" lowUpperBound="35" highLowerBound="90"/>
            <log type="testdox-text" target="_reports/testdox/executed.txt"/>
        </logging>
    </phpunit>

</code>


