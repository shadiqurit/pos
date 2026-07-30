<?php

declare(strict_types=1);

use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\BaseResult;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;

defined('LEGACY_APPPATH') || define('LEGACY_APPPATH', ROOTPATH . 'legacy/application/');
defined('BASEPATH') || define('BASEPATH', SYSTEMPATH);

final class LegacyRedirectException extends RuntimeException
{
    public function __construct(public readonly string $target, public readonly int $status = 302)
    {
        parent::__construct('Redirect to ' . $target, $status);
    }
}

final class LegacyHttpException extends RuntimeException
{
    public function __construct(
        public readonly string $heading,
        string $message,
        public readonly int $status = 500,
    ) {
        parent::__construct($message, $status);
    }
}

final class LegacyRuntime
{
    private static ?object $controller = null;
    private static ?LegacyDatabase $database = null;

    public static function setController(object $controller): void
    {
        self::$controller = $controller;
    }

    public static function &controller(): object
    {
        if (self::$controller === null) {
            throw new LogicException('The legacy controller has not been initialized.');
        }

        return self::$controller;
    }

    public static function boot(object $controller): void
    {
        self::setController($controller);
        helper(['url', 'form']);

        $controller->db              = self::$database ??= new LegacyDatabase(db_connect());
        $controller->session         = new LegacySession();
        $controller->input           = new LegacyInput();
        $controller->security        = new LegacySecurity();
        $controller->form_validation = new LegacyFormValidation();
        $controller->lang            = new LegacyLanguage();
        $controller->uri             = new LegacyUri();
        $controller->output          = new LegacyOutput();
        $controller->load            = new LegacyLoader($controller);

        foreach ([
            'custom',
            'inventory',
            'accounts',
            'appinfo',
            'advance',
            'saas',
            'currency',
            'foreign_currency',
        ] as $helper) {
            $path = LEGACY_APPPATH . 'helpers/' . $helper . '_helper.php';
            if (is_file($path)) {
                require_once $path;
            }
        }
    }
}

#[AllowDynamicProperties]
class CI_Controller
{
    public function __construct()
    {
        LegacyRuntime::boot($this);
    }
}

#[AllowDynamicProperties]
class CI_Model
{
    public function __construct()
    {
    }

    public function __get(string $name): mixed
    {
        $controller = LegacyRuntime::controller();

        return $controller->{$name} ?? null;
    }

    public function __set(string $name, mixed $value): void
    {
        LegacyRuntime::controller()->{$name} = $value;
    }
}

final class LegacyLoader
{
    /** @var array<string, mixed> Variables made available to nested CI3 views. */
    private array $viewData = [];

    public function __construct(private readonly object $controller)
    {
    }

    public function model(string $name, ?string $alias = null): object
    {
        $name = trim($name, '/\\');
        $file = LEGACY_APPPATH . 'models/' . $name . '.php';
        if (! is_file($file)) {
            $file = LEGACY_APPPATH . 'models/' . ucfirst($name) . '.php';
        }
        if (! is_file($file)) {
            throw new RuntimeException('Legacy model not found: ' . $name);
        }

        require_once $file;
        $class = basename(str_replace('\\', '/', $name));
        if (! class_exists($class)) {
            $class = ucfirst($class);
        }
        if (! class_exists($class)) {
            throw new RuntimeException('Legacy model class not found: ' . $class);
        }

        $instance = new $class();
        $property = $alias ?: strtolower(basename(str_replace('\\', '/', $name)));
        $this->controller->{$property} = $instance;

        return $instance;
    }

    public function view(string $view, array $data = [], bool $return = false): ?string
    {
        // CI3 accepts `folder/view` and `folder/view.php`. Several bundled
        // views use the latter for shared partials, so avoid `view.php.php`.
        $view = trim(str_replace('\\', '/', $view), '/');
        $view = preg_replace('/\.php$/i', '', $view) ?? $view;
        $path = LEGACY_APPPATH . 'views/' . $view . '.php';
        if (! is_file($path)) {
            throw new RuntimeException('Legacy view not found: ' . $view);
        }

        // CI3 keeps page variables available when a page includes a shared
        // partial such as `comman/code_css.php`.
        $this->viewData = array_merge($this->viewData, $data);
        $data = array_merge($this->viewData, $data);

        $render = function (string $__path, array $__data): void {
            extract($__data, EXTR_SKIP);
            include $__path;
        };

        ob_start();
        $render->call($this->controller, $path, $data);
        $contents = (string) ob_get_clean();

        if ($return) {
            return $contents;
        }

        echo $contents;

        return null;
    }

    public function helper(string|array $helpers): void
    {
        foreach ((array) $helpers as $helper) {
            // CI3 accepts both "name" and "name_helper". Normalize the latter
            // so compatibility requests do not become "name_helper_helper.php".
            $helperName = preg_replace('/_helper$/i', '', (string) $helper);
            $path = LEGACY_APPPATH . 'helpers/' . $helperName . '_helper.php';
            if (is_file($path)) {
                require_once $path;
            } else {
                helper($helperName);
            }
        }
    }

    public function library(string $name, array $config = [], ?string $alias = null): object
    {
        $normalized = strtolower(trim($name, '/\\'));
        $property   = $alias ?: basename($normalized);

        $instance = match ($normalized) {
            'session'  => $this->controller->session,
            'upload'   => new LegacyUpload($config),
            'image_lib' => new LegacyImageLibrary($config),
            'email'    => new LegacyEmail($config),
            default    => $this->loadCustomLibrary($name, $config),
        };

        $this->controller->{$property} = $instance;

        return $instance;
    }

    public function dbutil(): LegacyDbUtil
    {
        return $this->controller->dbutil = new LegacyDbUtil($this->controller->db);
    }

    private function loadCustomLibrary(string $name, array $config): object
    {
        $relative = trim(str_replace('\\', '/', $name), '/');
        $file     = LEGACY_APPPATH . 'libraries/' . $relative . '.php';
        if (! is_file($file)) {
            $parts    = explode('/', $relative);
            $parts[]  = ucfirst((string) array_pop($parts));
            $file     = LEGACY_APPPATH . 'libraries/' . implode('/', $parts) . '.php';
        }
        if (! is_file($file)) {
            throw new RuntimeException('Legacy library not found: ' . $name);
        }

        require_once $file;
        $class = basename($relative);
        if (! class_exists($class)) {
            $class = ucfirst($class);
        }

        return new $class($config);
    }
}

final class LegacyDatabase
{
    private ?string $table = null;
    private array $operations = [];
    private array $setData = [];
    public string $database;

    public function __construct(private readonly BaseConnection $connection)
    {
        $this->database = (string) $connection->getDatabase();
    }

    public function select(string|array $select = '*', bool $escape = true): self
    {
        return $this->operation('select', [$select, $escape]);
    }

    public function select_sum(string $select = '', string $alias = ''): self
    {
        return $this->operation('selectSum', [$select, $alias]);
    }

    public function from(string $table): self
    {
        if ($this->table === null) {
            $this->table = $table;
        } else {
            $this->operation('from', [$table]);
        }

        return $this;
    }

    public function where(mixed $key, mixed $value = null, ?bool $escape = null): self
    {
        return $this->operation('where', [$key, $value, $escape]);
    }

    public function or_where(mixed $key, mixed $value = null, ?bool $escape = null): self
    {
        return $this->operation('orWhere', [$key, $value, $escape]);
    }

    public function where_in(string $key, array $values, ?bool $escape = null): self
    {
        return $this->operation('whereIn', [$key, $values, $escape]);
    }

    public function join(string $table, string $condition, string $type = '', ?bool $escape = null): self
    {
        return $this->operation('join', [$table, $condition, $type, $escape]);
    }

    public function order_by(string $orderBy, string $direction = '', ?bool $escape = null): self
    {
        return $this->operation('orderBy', [$orderBy, $direction, $escape]);
    }

    public function group_by(string|array $groupBy, ?bool $escape = null): self
    {
        return $this->operation('groupBy', [$groupBy, $escape]);
    }

    public function like(mixed $field, string $match = '', string $side = 'both', ?bool $escape = null): self
    {
        return $this->operation('like', [$field, $match, $side, $escape]);
    }

    public function or_like(mixed $field, string $match = '', string $side = 'both', ?bool $escape = null): self
    {
        return $this->operation('orLike', [$field, $match, $side, $escape]);
    }

    public function group_start(): self
    {
        return $this->operation('groupStart');
    }

    public function group_end(): self
    {
        return $this->operation('groupEnd');
    }

    public function limit(?int $value = null, ?int $offset = 0): self
    {
        return $this->operation('limit', [$value, $offset]);
    }

    public function set(string|array $key, mixed $value = '', ?bool $escape = null): self
    {
        if (is_array($key)) {
            $this->setData = array_merge($this->setData, $key);
        } else {
            $this->setData[$key] = $value;
        }

        return $this;
    }

    public function get(?string $table = null, ?int $limit = null, ?int $offset = 0): LegacyResult
    {
        if ($table !== null) {
            $this->table = $table;
        }
        if ($limit !== null) {
            $this->limit($limit, $offset);
        }

        $result = $this->builder()->get();
        $this->reset();

        return new LegacyResult($result);
    }

    public function query(string $sql, mixed $binds = null): LegacyResult|bool
    {
        $result = $this->connection->query($sql, $binds);

        return $result instanceof BaseResult ? new LegacyResult($result) : (bool) $result;
    }

    public function simple_query(string $sql): bool
    {
        return (bool) $this->connection->simpleQuery($sql);
    }

    public function insert(string $table, ?array $data = null): bool
    {
        $builder = $this->connection->table($table);
        $payload = $data ?? $this->setData;
        $result  = $builder->insert($payload);
        $this->reset();

        return $result;
    }

    public function insert_batch(string $table, array $data): int|false
    {
        $result = $this->connection->table($table)->insertBatch($data);
        $this->reset();

        return $result;
    }

    public function update(string $table, ?array $data = null, mixed $where = null): bool
    {
        $builder = $this->builder($table);
        if ($where !== null) {
            $builder->where($where);
        }
        $result = $builder->update($data ?? $this->setData);
        $this->reset();

        return $result;
    }

    public function delete(string $table = '', mixed $where = '', ?int $limit = null, bool $resetData = true): bool
    {
        $builder = $this->builder($table !== '' ? $table : null);
        if ($where !== '' && $where !== null) {
            $builder->where($where);
        }
        if ($limit !== null) {
            $builder->limit($limit);
        }
        $result = $builder->delete();
        if ($resetData) {
            $this->reset();
        }

        return $result;
    }

    public function count_all_results(string $table = '', bool $reset = true): int
    {
        $builder = $this->builder($table !== '' ? $table : null);
        $count   = $builder->countAllResults($reset);
        if ($reset) {
            $this->reset();
        }

        return $count;
    }

    public function get_compiled_select(string $table = '', bool $reset = true): string
    {
        if ($table === '' && ($this->table === null || $this->table === '')) {
            return '';
        }

        $sql = $this->builder($table !== '' ? $table : null)->getCompiledSelect($reset);
        if ($reset) {
            $this->reset();
        }

        return $sql;
    }

    public function insert_id(): int
    {
        return (int) $this->connection->insertID();
    }

    public function affected_rows(): int
    {
        return $this->connection->affectedRows();
    }

    public function trans_begin(bool $testMode = false): bool
    {
        return $this->connection->transBegin($testMode);
    }

    public function trans_commit(): bool
    {
        return $this->connection->transCommit();
    }

    public function trans_rollback(): bool
    {
        return $this->connection->transRollback();
    }

    public function trans_strict(bool $mode = true): void
    {
        $this->connection->transStrict($mode);
    }

    public function error(): array
    {
        return $this->connection->error();
    }

    public function connection(): BaseConnection
    {
        return $this->connection;
    }

    private function operation(string $method, array $arguments = []): self
    {
        $this->operations[] = [$method, $arguments];

        return $this;
    }

    private function builder(?string $table = null): BaseBuilder
    {
        $table ??= $this->table;
        if ($table === null || $table === '') {
            throw new LogicException('No table was selected for the legacy query builder.');
        }

        $builder = $this->connection->table($table);
        foreach ($this->operations as [$method, $arguments]) {
            $builder->{$method}(...$arguments);
        }

        return $builder;
    }

    private function reset(): void
    {
        $this->table      = null;
        $this->operations = [];
        $this->setData    = [];
    }
}

final class LegacyResult
{
    public function __construct(private readonly BaseResult $result)
    {
    }

    public function row(int $index = 0): ?object
    {
        return $this->result->getRow($index);
    }

    public function row_array(int $index = 0): ?array
    {
        return $this->result->getRowArray($index);
    }

    public function result(): array
    {
        return $this->result->getResult();
    }

    public function result_array(): array
    {
        return $this->result->getResultArray();
    }

    public function num_rows(): int
    {
        return $this->result->getNumRows();
    }

    public function first_row(): ?object
    {
        return $this->result->getFirstRow();
    }
}

final class LegacySession
{
    public function userdata(?string $key = null): mixed
    {
        return $key === null ? session()->get() : session()->get($key);
    }

    public function set_userdata(string|array $key, mixed $value = null): void
    {
        session()->set($key, $value);
    }

    public function has_userdata(string $key): bool
    {
        return session()->has($key);
    }

    public function unset_userdata(string|array $key): void
    {
        session()->remove($key);
    }

    public function set_flashdata(string $key, mixed $value): void
    {
        session()->setFlashdata($key, $value);
    }

    public function flashdata(?string $key = null): mixed
    {
        return $key === null ? session()->getFlashdata() : session()->getFlashdata($key);
    }

    public function sess_destroy(): void
    {
        session()->destroy();
    }
}

final class LegacyInput
{
    public function post(?string $key = null, bool $xssClean = false): mixed
    {
        return $key === null ? service('request')->getPost() : service('request')->getPost($key);
    }

    public function get(?string $key = null, bool $xssClean = false): mixed
    {
        return $key === null ? service('request')->getGet() : service('request')->getGet($key);
    }

    public function get_post(?string $key = null, bool $xssClean = false): mixed
    {
        return service('request')->getVar($key);
    }

    public function cookie(?string $key = null, bool $xssClean = false): mixed
    {
        return $key === null ? service('request')->getCookie() : service('request')->getCookie($key);
    }

    public function set_cookie(array|string $name, string $value = '', int $expire = 0): void
    {
        if (is_array($name)) {
            setcookie(
                (string) $name['name'],
                (string) ($name['value'] ?? ''),
                time() + (int) ($name['expire'] ?? 0),
                (string) ($name['path'] ?? '/'),
            );

            return;
        }

        setcookie($name, $value, time() + $expire, '/');
    }
}

final class LegacySecurity
{
    public function xss_clean(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map([$this, 'xss_clean'], $value);
        }

        return is_string($value) ? strip_tags($value) : $value;
    }

    public function get_csrf_token_name(): string
    {
        return csrf_token();
    }

    public function get_csrf_hash(): string
    {
        return csrf_hash();
    }
}

final class LegacyFormValidation
{
    private array $rules = [];
    private array $errors = [];

    public function set_rules(string $field, string $label = '', string|array $rules = ''): self
    {
        $this->rules[$field] = [
            'label' => $label !== '' ? $label : $field,
            'rules' => $this->normalizeRules($rules),
        ];

        return $this;
    }

    public function run(): bool
    {
        $validation = service('validation');
        $validation->reset();
        $validation->setRules($this->rules);
        $valid        = $validation->run((array) service('request')->getPost());
        $this->errors = $validation->getErrors();

        return $valid;
    }

    public function error(string $field, string $prefix = '', string $suffix = ''): string
    {
        return isset($this->errors[$field]) ? $prefix . $this->errors[$field] . $suffix : '';
    }

    public function errors(): array
    {
        return $this->errors;
    }

    private function normalizeRules(string|array $rules): string|array
    {
        if (! is_string($rules)) {
            return $rules;
        }

        $parts = array_filter(
            explode('|', $rules),
            static fn (string $rule): bool => ! in_array($rule, ['trim', 'xss_clean'], true),
        );

        return implode('|', $parts);
    }
}

final class LegacyLanguage
{
    private array $lines = [];

    public function load(string $file, string $language = 'English'): bool
    {
        $path = LEGACY_APPPATH . 'language/' . $language . '/' . $file . '_lang.php';
        if (! is_file($path)) {
            return false;
        }

        $lines = (static function (string $__path): array {
            $lang = [];
            include $__path;

            return $lang;
        })($path);

        $this->lines = array_merge($this->lines, $lines);

        return true;
    }

    public function line(string $line): string
    {
        return (string) ($this->lines[$line] ?? $line);
    }
}

final class LegacyUri
{
    public function segment(int $number, mixed $default = null): mixed
    {
        return service('request')->getUri()->getSegment($number, is_string($default) ? $default : '');
    }
}

final class LegacyOutput
{
    public function get_output(): string
    {
        return ob_get_contents() ?: '';
    }

    public function enable_profiler(bool $enabled = true): self
    {
        return $this;
    }
}

final class LegacyUpload
{
    private array $data = [];
    private string $error = '';

    public function __construct(private array $config = [])
    {
    }

    public function initialize(array $config): self
    {
        $this->config = $config;

        return $this;
    }

    public function do_upload(string $field = 'userfile'): bool
    {
        $file = service('request')->getFile($field);
        if ($file === null || ! $file->isValid()) {
            $this->error = $file?->getErrorString() ?? 'No uploaded file was received.';

            return false;
        }

        $extension = strtolower($file->getClientExtension());
        $allowed   = array_filter(explode('|', strtolower((string) ($this->config['allowed_types'] ?? ''))));
        if ($allowed !== [] && ! in_array($extension, $allowed, true)) {
            $this->error = 'The file type you are attempting to upload is not allowed.';

            return false;
        }

        $maximumSize = (float) ($this->config['max_size'] ?? 0);
        if ($maximumSize > 0 && $file->getSizeByUnit('kb') > $maximumSize) {
            $this->error = 'The uploaded file exceeds the allowed size.';

            return false;
        }

        if (($this->config['max_width'] ?? 0) || ($this->config['max_height'] ?? 0)) {
            $dimensions = @getimagesize($file->getTempName());
            if ($dimensions === false) {
                $this->error = 'The uploaded file is not a valid image.';

                return false;
            }

            $maximumWidth  = (int) ($this->config['max_width'] ?? 0);
            $maximumHeight = (int) ($this->config['max_height'] ?? 0);
            if (($maximumWidth > 0 && $dimensions[0] > $maximumWidth)
                || ($maximumHeight > 0 && $dimensions[1] > $maximumHeight)) {
                $this->error = 'The uploaded image exceeds the allowed dimensions.';

                return false;
            }
        }

        $destination = $this->config['upload_path'] ?? (FCPATH . 'uploads');
        $destination = str_replace(['./uploads', '.\\uploads'], FCPATH . 'uploads', $destination);
        $destination = rtrim($destination, '/\\');
        if (! is_dir($destination) && ! mkdir($destination, 0775, true) && ! is_dir($destination)) {
            $this->error = 'The upload destination could not be created.';

            return false;
        }

        $configuredName = $this->config['file_name'] ?? null;
        if ($configuredName !== null && $configuredName !== '') {
            $name = (string) $configuredName;
            if ($extension !== '' && pathinfo($name, PATHINFO_EXTENSION) === '') {
                $name .= '.' . $extension;
            }
        } else {
            $name = $file->getRandomName();
        }

        try {
            $file->move($destination, $name, true);
        } catch (\Throwable $exception) {
            $this->error = $exception->getMessage();

            return false;
        }

        $this->data = [
            'file_name'     => $file->getName(),
            'full_path'     => $destination . DIRECTORY_SEPARATOR . $file->getName(),
            'file_type'     => $file->getClientMimeType(),
            'file_size'     => $file->getSizeByUnit('kb'),
            'original_name' => $file->getClientName(),
        ];

        return true;
    }

    public function data(?string $key = null): mixed
    {
        return $key === null ? $this->data : ($this->data[$key] ?? null);
    }

    public function display_errors(string $open = '<p>', string $close = '</p>'): string
    {
        return $this->error === '' ? '' : $open . $this->error . $close;
    }
}

final class LegacyImageLibrary
{
    public function __construct(private array $config = [])
    {
    }

    public function initialize(array $config): self
    {
        $this->config = $config;

        return $this;
    }

    public function resize(): bool
    {
        $source = $this->config['source_image'] ?? null;
        if (! is_string($source)) {
            return false;
        }

        $source = $this->resolvePath($source);
        if (! is_file($source)) {
            return false;
        }

        $target = $this->config['new_image'] ?? null;
        if (is_string($target) && $target !== '') {
            $target = $this->resolvePath($target);
        } elseif (! empty($this->config['create_thumb'])) {
            $extension = pathinfo($source, PATHINFO_EXTENSION);
            $suffix    = $extension === '' ? '' : '.' . $extension;
            $target    = substr($source, 0, strlen($source) - strlen($suffix)) . '_thumb' . $suffix;
        } else {
            $target = $source;
        }

        try {
            service('image')
                ->withFile($source)
                ->resize(
                    (int) ($this->config['width'] ?? 150),
                    (int) ($this->config['height'] ?? 150),
                    (bool) ($this->config['maintain_ratio'] ?? true),
                    'auto',
                )
                ->save($target);
        } catch (\Throwable) {
            return false;
        }

        return true;
    }

    private function resolvePath(string $path): string
    {
        if (is_file($path) || str_starts_with($path, FCPATH)) {
            return $path;
        }

        $relative = ltrim(str_replace('\\', '/', $path), '/');

        return FCPATH . str_replace('/', DIRECTORY_SEPARATOR, $relative);
    }
}

final class LegacyEmail
{
    private \CodeIgniter\Email\Email $email;

    public function __construct(array $config = [])
    {
        $this->email = service('email');
        if ($config !== []) {
            $this->email->initialize($config);
        }
    }

    public function __call(string $name, array $arguments): mixed
    {
        $method = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $name))));

        return $this->email->{$method}(...$arguments);
    }
}

final class LegacyDbUtil
{
    public function __construct(private readonly LegacyDatabase $database)
    {
    }

    public function backup(array $preferences = []): string
    {
        return service('dbutil', $this->database->connection())->backup($preferences);
    }
}

if (! function_exists('get_instance')) {
    function &get_instance(): object
    {
        $controller =& LegacyRuntime::controller();

        return $controller;
    }
}

if (! function_exists('legacy_redirect')) {
    function legacy_redirect(string $uri = '', string $method = 'auto', ?int $code = null): never
    {
        $target = preg_match('#^https?://#i', $uri) ? $uri : site_url($uri);
        throw new LegacyRedirectException($target, $code ?? 302);
    }
}

if (! function_exists('legacy_show_error')) {
    function legacy_show_error(string $message, int $statusCode = 500, string $heading = 'An Error Was Encountered'): never
    {
        throw new LegacyHttpException($heading, $message, $statusCode);
    }
}

if (! function_exists('html_escape')) {
    function html_escape(mixed $value): mixed
    {
        return is_array($value)
            ? array_map('html_escape', $value)
            : htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (! function_exists('config_item')) {
    function config_item(string $item): mixed
    {
        static $legacyConfig = null;

        if ($legacyConfig === null) {
            $config = [];
            $path   = LEGACY_APPPATH . 'config/config.php';
            if (is_file($path)) {
                include $path;
            }
            $legacyConfig = $config;
        }

        return $legacyConfig[$item] ?? null;
    }
}

if (! function_exists('form_error')) {
    function form_error(string $field, string $prefix = '', string $suffix = ''): string
    {
        return LegacyRuntime::controller()->form_validation->error($field, $prefix, $suffix);
    }
}

if (! function_exists('validation_errors')) {
    function validation_errors(string $prefix = '', string $suffix = ''): string
    {
        $errors = LegacyRuntime::controller()->form_validation->errors();

        return implode('', array_map(
            static fn (string $error): string => $prefix . $error . $suffix,
            $errors,
        ));
    }
}
