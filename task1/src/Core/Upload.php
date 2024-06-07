<?php

declare(strict_types = 1);

namespace Core;

class Upload
{
    /**
     * @var \Core\Security
     */
    protected Security $security;
    /**
     * @var false|mixed|string
     */
    protected $file_type;
    /**
     * Obfuscate filename flag
     *
     * @var    bool
     */
    public bool $encrypt_name = false;
    /**
     * @var mixed
     */
    protected        $file_size;
    protected string $file_ext;
    protected string $clientName;
    protected string $_file_name_override;
    /**
     * Maximum file size
     */
    public int $max_size = 0;
    /**
     * Remove spaces flag
     *
     * @var    bool
     */
    public bool $remove_spaces = true;

    /**
     * Maximum image width
     */
    public int $max_width = 0;

    /**
     * Maximum image height
     *
     * @var    int
     */
    public int $max_height = 0;

    /**
     * Minimum image width
     *
     * @var    int
     */
    public int $min_width = 0;

    /**
     * Minimum image height
     *
     * @var    int
     */
    public int $min_height = 0;

    /**
     * Maximum filename length
     *
     * @var    int
     */
    public int $max_filename = 250;

    /**
     * Maximum duplicate filename increment ID
     *
     * @var    int
     */
    public int $max_filename_increment = 100;
    /**
     * @var array|string|string[]|null
     */
    protected $orig_name;

    /**
     * Temporary filename
     *
     * @var    string
     */
    protected string $file_temp = '';

    /**
     * Filename
     *
     * @var    string
     */
    protected string $file_name   = '';
    protected string $upload_path = '';
    /**
     * MIME types list
     *
     * @var    array
     */
    protected array $_mimes       = [];
    public bool     $mod_mime_fix = true;
    /**
     * Force filename extension to lowercase
     *
     * @var    string
     */
    public bool $file_ext_to_lower     = false;
    public bool $overWriteFileIfExists = false;

    private const UPLOAD_ERRORS = [
        UPLOAD_ERR_OK         => 'There is no error',
        UPLOAD_ERR_INI_SIZE   => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
        UPLOAD_ERR_FORM_SIZE  => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
        UPLOAD_ERR_PARTIAL    => 'The uploaded file was only partially uploaded.',
        UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
        UPLOAD_ERR_EXTENSION  => 'A PHP extension stopped the file upload.',
    ];
    protected bool $xss_clean = true;

    public function __construct()
    {
        $this->security = new Security();
        $this->_mimes =& get_mimes();
    }

    /**
     * @param          $userId
     * @param  string  $field
     *
     * @return bool|null
     */
    public function uploadUserAvatar($userId, string $field = 'user_photo'): ?bool
    {
        $this->overWriteFileIfExists = true;
        $this->_file_name_override = $userId . '_avatar';
        return $this->upload($field, true);
    }

    /**
     * @param  string  $field
     *
     * @return bool
     */
    private function upload(string $field = 'image', $isUserAvatar = false): bool
    {
        $_file = null;
        if (isset($_FILES[$field])) {
            $_file = $_FILES[$field];
        } elseif (($c = preg_match_all('/(?:^[^\[]+)|\[[^]]*\]/', $field, $matches)) > 1) {
            // Does the field name contain array notation?
            $_file = $_FILES;
            for ($i = 0; $i < $c; $i++) {
                // We can't track numeric iterations, only full field names are accepted
                if (($field = trim($matches[0][$i], '[]')) === '' or !isset($_file[$field])) {
                    $_file = null;
                    break;
                }

                $_file = $_file[$field];
            }
        }

        if (is_null($_file)) {
            $_SESSION['errors'][] = self::UPLOAD_ERRORS[UPLOAD_ERR_NO_FILE];
            return false;
        }

        // Is the upload path valid?
        if (!$this->validate_upload_path($isUserAvatar)) {
            return false;
        }


        // Was the file able to be uploaded? If not, determine the reason why.
        if (!is_uploaded_file($_file['tmp_name'])) {
            $error = $_file['error'] ?? UPLOAD_ERR_NO_FILE;

            switch ($error) {
                case UPLOAD_ERR_INI_SIZE:
                    $_SESSION['errors'][] = self::UPLOAD_ERRORS[UPLOAD_ERR_INI_SIZE];
                    break;
                case UPLOAD_ERR_FORM_SIZE:
                    $_SESSION['errors'][] = self::UPLOAD_ERRORS[UPLOAD_ERR_FORM_SIZE];
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $_SESSION['errors'][] = self::UPLOAD_ERRORS[UPLOAD_ERR_PARTIAL];
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                    $_SESSION['errors'][] = self::UPLOAD_ERRORS[UPLOAD_ERR_NO_TMP_DIR];
                    break;
                case UPLOAD_ERR_CANT_WRITE:
                    $_SESSION['errors'][] = self::UPLOAD_ERRORS[UPLOAD_ERR_CANT_WRITE];
                    break;
                case UPLOAD_ERR_EXTENSION:
                    $_SESSION['errors'][] = self::UPLOAD_ERRORS[UPLOAD_ERR_EXTENSION];
                    break;
                default:
                    $_SESSION['errors'][] = self::UPLOAD_ERRORS[UPLOAD_ERR_NO_FILE];
                    break;
            }
            return false;
        }

        // Set the uploaded data as class variables
        $this->file_temp = $_file['tmp_name'];
        $this->file_size = $_file['size'];


        $this->_file_mime_type($_file);


        $this->file_type = preg_replace('/^(.+?);.*$/', '\\1', $this->file_type);
        $this->file_type = strtolower(trim(stripslashes($this->file_type), '"'));
        $this->file_name = $this->_prep_filename($_file['name']);
        $this->file_ext = $this->get_extension($this->file_name);

        // Is the file type allowed to be uploaded?
        if (!$this->is_allowed_filetype()) {
            $_SESSION['errors'][] = 'Invalid file type';
            return false;
        }

        // if we're overriding, let's now make sure the new name and type is allowed
        if ($this->_file_name_override !== '') {
            $this->file_name = $this->_prep_filename($this->_file_name_override);

            // If no extension was provided in the file_name config item, use the uploaded one
            if (strpos($this->_file_name_override, '.') === false) {
                $this->file_name .= $this->file_ext;
            } else {
                // An extension was provided, let's have it!
                $this->file_ext = $this->get_extension($this->_file_name_override);
            }

            if (!$this->is_allowed_filetype(true)) {
                $_SESSION['errors'][] = 'Invalid file type';
                return false;
            }
        }

        // Convert the file size to kilobytes
        if ($this->file_size > 0) {
            $this->file_size = round($this->file_size / 1024, 2);
        }

        // Is the file size within the allowed maximum?
        if (!$this->is_allowed_filesize()) {
            $_SESSION['errors'][] = 'File too large';
            return false;
        }

        // Are the image dimensions within the allowed size?
        // Note: This can fail if the server has an open_basedir restriction.
        if (!$this->is_allowed_dimensions()) {
            $_SESSION['errors'][] = 'File exceeds allowed dimensions';
            return false;
        }

        // Sanitize the file name for security
        $this->file_name = $this->security->sanitize_filename($this->file_name);

        // Truncate the file name if it's too long
        if ($this->max_filename > 0) {
            $this->file_name = $this->limit_filename_length($this->file_name, $this->max_filename);
        }

        // Remove white spaces in the name
        if ($this->remove_spaces === true) {
            $this->file_name = preg_replace('/\s+/', '_', $this->file_name);
        }

        if ($this->file_ext_to_lower && ($ext_length = strlen($this->file_ext))) {
            // file_ext was previously lower-cased by a get_extension() call
            $this->file_name = substr($this->file_name, 0, -$ext_length) . $this->file_ext;
        }

        /*
         * Validate the file name
         * This function appends an number onto the end of
         * the file if one with the same name already exists.
         * If it returns false there was a problem.
         */
        $this->orig_name = $this->file_name;
        if (false === ($this->file_name = $this->set_filename($this->upload_path, $this->file_name))) {
            return false;
        }


        /*
         * Run the file through the XSS hacking filter
         * This helps prevent malicious code from being
         * embedded within a file. Scripts can easily
         * be disguised as images or other file types.
         */
        if ($this->xss_clean && $this->do_xss_clean() === false) {
            $_SESSION['errors'][] = 'Unable to write to upload file';
            return false;
        }

        /*
         * Move the file to the final destination
         * To deal with different server configurations
         * we'll attempt to use copy() first. If that fails
         * we'll use move_uploaded_file(). One of the two should
         * reliably work in most environments
         */
        if (!@copy($this->file_temp, $this->upload_path . $this->file_name)) {
            if (!@move_uploaded_file($this->file_temp, $this->upload_path . $this->file_name)) {
                $_SESSION['errors'][] = 'Failed to move uploaded file';
                return false;
            }
        }

        return true;
    }

    /**
     * @return bool|string|string[]
     */
    public function do_xss_clean()
    {
        $file = $this->file_temp;

        if (filesize($file) == 0) {
            return false;
        }

        if (memory_get_usage() && ($memory_limit = ini_get('memory_limit')) > 0) {
            $memory_limit = str_split($memory_limit, strspn($memory_limit, '1234567890'));
            if (!empty($memory_limit[1])) {
                switch ($memory_limit[1][0]) {
                    case 'g':
                    case 'G':
                        $memory_limit[0] *= 1073741824;
                        break;
                    case 'm':
                    case 'M':
                        $memory_limit[0] *= 1048576;
                        break;
                    default:
                        break;
                }
            }

            $memory_limit = (int) ceil(filesize($file) + $memory_limit[0]);
            ini_set('memory_limit', (string) $memory_limit); // When an integer is used, the value is measured in bytes. - PHP.net
        }

        // If the file being uploaded is an image, then we should have no problem with XSS attacks (in theory), but
        // IE can be fooled into mime-type detecting a malformed image as an html file, thus executing an XSS attack on anyone
        // using IE who looks at the image. It does this by inspecting the first 255 bytes of an image. To get around this
        // CI will itself look at the first 255 bytes of an image to determine its relative safety. This can save a lot of
        // processor power and time if it is actually a clean image, as it will be in nearly all instances _except_ an
        // attempted XSS attack.

        if (function_exists('getimagesize') && @getimagesize($file) !== false) {
            if (($file = @fopen($file, 'rb')) === false) // "b" to force binary
            {
                return false; // Couldn't open the file, return FALSE
            }

            $opening_bytes = fread($file, 256);
            fclose($file);

            // These are known to throw IE into mime-type detection chaos
            // <a, <body, <head, <html, <img, <plaintext, <pre, <script, <table, <title
            // title is basically just in SVG, but we filter it anyhow

            // if it's an image or no "triggers" detected in the first 256 bytes - we're good
            return !preg_match('/<(a|body|head|html|img|plaintext|pre|script|table|title)[\s>]/i', $opening_bytes);
        }

        if (($data = @file_get_contents($file)) === false) {
            return false;
        }

        return $this->security->xss_clean($data, true);
    }

    /**
     * File MIME type
     *
     * Detects the (actual) MIME type of the uploaded file, if possible.
     * The input array is expected to be $_FILES[$field]
     *
     * @param  array  $file
     *
     * @return    void
     */
    protected function _file_mime_type($file)
    {
        // We'll need this to validate the MIME info string (e.g. text/plain; charset=us-ascii)
        $regexp = '/^([a-z\-]+\/[a-z0-9\-\.\+]+)(;\s.+)?$/';

        /**
         * Fileinfo extension - most reliable method
         *
         * Apparently XAMPP, CentOS, cPanel and who knows what
         * other PHP distribution channels EXPLICITLY DISABLE
         * ext/fileinfo, which is otherwise enabled by default
         * since PHP 5.3 ...
         */
        if (function_exists('finfo_file')) {
            $finfo = @finfo_open(FILEINFO_MIME);
            if (is_resource($finfo)) // It is possible that a FALSE value is returned, if there is no magic MIME database file found on the system
            {
                $mime = @finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);

                /* According to the comments section of the PHP manual page,
                 * it is possible that this function returns an empty string
                 * for some files (e.g. if they don't exist in the magic MIME database)
                 */
                if (is_string($mime) && preg_match($regexp, $mime, $matches)) {
                    $this->file_type = $matches[1];
                    return;
                }
            }
        }

        /* This is an ugly hack, but UNIX-type systems provide a "native" way to detect the file type,
         * which is still more secure than depending on the value of $_FILES[$field]['type'], and as it
         * was reported in issue #750 (https://github.com/EllisLab/CodeIgniter/issues/750) - it's better
         * than mime_content_type() as well, hence the attempts to try calling the command line with
         * three different functions.
         *
         * Notes:
         *	- the DIRECTORY_SEPARATOR comparison ensures that we're not on a Windows system
         *	- many system admins would disable the exec(), shell_exec(), popen() and similar functions
         *	  due to security concerns, hence the function_usable() checks
         */
        if (DIRECTORY_SEPARATOR !== '\\') {
            $cmd = function_exists('escapeshellarg')
                ? 'file --brief --mime ' . escapeshellarg($file['tmp_name']) . ' 2>&1'
                : 'file --brief --mime ' . $file['tmp_name'] . ' 2>&1';

            if (function_usable('exec')) {
                /* This might look confusing, as $mime is being populated with all of the output when set in the second parameter.
                 * However, we only need the last line, which is the actual return value of exec(), and as such - it overwrites
                 * anything that could already be set for $mime previously. This effectively makes the second parameter a dummy
                 * value, which is only put to allow us to get the return status code.
                 */
                $mime = @exec($cmd, $mime, $return_status);
                if ($return_status === 0 && is_string($mime) && preg_match($regexp, $mime, $matches)) {
                    $this->file_type = $matches[1];
                    return;
                }
            }

            if (!ini_get('safe_mode') && function_usable('shell_exec')) {
                $mime = @shell_exec($cmd);
                if (strlen($mime) > 0) {
                    $mime = explode("\n", trim($mime));
                    if (preg_match($regexp, $mime[(count($mime) - 1)], $matches)) {
                        $this->file_type = $matches[1];
                        return;
                    }
                }
            }

            if (function_usable('popen')) {
                $proc = @popen($cmd, 'r');
                if (is_resource($proc)) {
                    $mime = @fread($proc, 512);
                    @pclose($proc);
                    if ($mime !== false) {
                        $mime = explode("\n", trim($mime));
                        if (preg_match($regexp, $mime[(count($mime) - 1)], $matches)) {
                            $this->file_type = $matches[1];
                            return;
                        }
                    }
                }
            }
        }

        // Fall back to mime_content_type(), if available (still better than $_FILES[$field]['type'])
        if (function_exists('mime_content_type')) {
            $this->file_type = @mime_content_type($file['tmp_name']);
            if (strlen($this->file_type) > 0) // It's possible that mime_content_type() returns FALSE or an empty string
            {
                return;
            }
        }

        $this->file_type = $file['type'];
    }

    /**
     * Validate Upload Path
     *
     * Verifies that it is a valid upload path with proper permissions.
     *
     * @return    bool
     */
    private function validate_upload_path(bool $isUserPhotoUpload = false)
    {
        $uploadPath = $isUserPhotoUpload ? USER_AVATARS_UPLOAD_DIR : SITE_IMAGES_UPLOAD_DIR;

        if ($uploadPath === '') {
            $_SESSION['errors'][] = 'No file path for upload';
            return false;
        }

        if (realpath($uploadPath) !== false) {
            $uploadPath = str_replace('\\', '/', realpath($uploadPath));
        }

        if (!is_dir($uploadPath)) {
            $_SESSION['errors'][] = 'No file path for upload';
            return false;
        }

        if (!is_really_writable($uploadPath)) {
            $_SESSION['errors'][] = 'Upload dir is not writeable';
            return false;
        }

        $this->upload_path = preg_replace('/(.+?)\/*$/', '\\1/', $uploadPath);
        return true;
    }

    /**
     * Prep Filename
     *
     * Prevents possible script execution from Apache's handling
     * of files' multiple extensions.
     *
     * @link    http://httpd.apache.org/docs/1.3/mod/mod_mime.html#multipleext
     *
     * @param  string  $filename
     *
     * @return    string
     */
    protected function _prep_filename(string $filename): string
    {
        if ($this->mod_mime_fix === false || ALLOWED_MIME_TYPES === [] or ($ext_pos = strrpos($filename, '.')) === false) {
            return $filename;
        }

        $ext = substr($filename, $ext_pos);
        $filename = substr($filename, 0, $ext_pos);
        return str_replace('.', '_', $filename) . $ext;
    }

    /**
     * Extract the file extension
     *
     * @param  string  $filename
     *
     * @return    string
     */
    public function get_extension(string $filename): string
    {
        $x = explode('.', $filename);

        if (count($x) === 1) {
            return '';
        }

        $ext = ($this->file_ext_to_lower) ? strtolower(end($x)) : end($x);
        return '.' . $ext;
    }

    /**
     * Verify that the filetype is allowed
     *
     * @param  bool  $ignore_mime
     *
     * @return    bool
     */
    public function is_allowed_filetype($ignore_mime = false)
    {
        if (ALLOWED_MIME_TYPES === []) {
            return true;
        }

        if (empty(ALLOWED_MIME_TYPES) || !is_array(ALLOWED_MIME_TYPES)) {
            $_SESSION['errors'][] = 'Allowed mime types must be specified.';
            return false;
        }

        $ext = strtolower(ltrim($this->file_ext, '.'));

        if (!in_array($ext, ALLOWED_MIME_TYPES, true) && !isset(ALLOWED_MIME_TYPES[$ext])) {
            $_SESSION['errors'][] = 'Not allowed mime type ' . $ext;
            return false;
        }

        // Images get some additional checks
        if (in_array($ext, ['gif', 'jpg', 'jpeg', 'jpe', 'png'], true) && @getimagesize($this->file_temp) === false) {
            $_SESSION['errors'][] = 'Not allowed mime type ' . $ext;
            return false;
        }

        if ($ignore_mime === true) {
            return true;
        }

        if (isset($this->_mimes[$ext])) {
            return is_array($this->_mimes[$ext])
                ? in_array($this->file_type, $this->_mimes[$ext], true)
                : ($this->_mimes[$ext] === $this->file_type);
        }

        return false;
    }

    /**
     * Verify that the file is within the allowed size
     *
     * @return    bool
     */
    private function is_allowed_filesize(): bool
    {
        return (MAX_ALLOWED_FILE_SIZE === 0 || 3000000 > $this->file_size);
    }

    /**
     * Verify that the image is within the allowed width/height
     *
     * @return    bool
     */
    private function is_allowed_dimensions()
    {
        if (!$this->is_image()) {
            return true;
        }

        if (function_exists('getimagesize')) {
            $D = @getimagesize($this->file_temp);

            if ($this->max_width > 0 && $D[0] > $this->max_width) {
                return false;
            }

            if ($this->max_height > 0 && $D[1] > $this->max_height) {
                return false;
            }

            if ($this->min_width > 0 && $D[0] < $this->min_width) {
                return false;
            }

            if ($this->min_height > 0 && $D[1] < $this->min_height) {
                return false;
            }
        }

        return true;
    }


    /**
     * Validate the image
     *
     * @return    bool
     */
    private function is_image(): bool
    {
        // IE will sometimes return odd mime-types during upload, so here we just standardize all
        // jpegs or pngs to the same file type.

        $png_mimes = ['image/x-png'];
        $jpeg_mimes = ['image/jpg', 'image/jpe', 'image/jpeg', 'image/pjpeg'];

        if (in_array($this->file_type, $png_mimes)) {
            $this->file_type = 'image/png';
        } elseif (in_array($this->file_type, $jpeg_mimes)) {
            $this->file_type = 'image/jpeg';
        }

        $img_mimes = ['image/gif', 'image/jpeg', 'image/png'];

        return in_array($this->file_type, $img_mimes, true);
    }

    /**
     * Limit the File Name Length
     *
     * @param  string  $filename
     * @param  int     $length
     *
     * @return    string
     */
    private function limit_filename_length($filename, $length)
    {
        if (strlen($filename) < $length) {
            return $filename;
        }

        $ext = '';
        if (strpos($filename, '.') !== false) {
            $parts = explode('.', $filename);
            $ext = '.' . array_pop($parts);
            $filename = implode('.', $parts);
        }

        return substr($filename, 0, ($length - strlen($ext))) . $ext;
    }

    /**
     * Set the file name
     *
     * This function takes a filename/path as input and looks for the
     * existence of a file with the same name. If found, it will append a
     * number to the end of the filename to avoid overwriting a pre-existing file.
     *
     * @param  string  $path
     * @param  string  $filename
     *
     * @return    string
     */
    public function set_filename($path, $filename)
    {
        if ($this->encrypt_name === true) {
            $filename = $this->security->uniqueIdsGenerator->generateTrueRandomString() . $this->file_ext;
        }

        if ($this->overWriteFileIfExists === true or !file_exists($path . $filename)) {
            return $filename;
        }

        $filename = str_replace($this->file_ext, '', $filename);

        $new_filename = '';
        for ($i = 1; $i < $this->max_filename_increment; $i++) {
            if (!file_exists($path . $filename . $i . $this->file_ext)) {
                $new_filename = $filename . $i . $this->file_ext;
                break;
            }
        }

        if ($new_filename === '') {
            $_SESSION['errors'][] = 'Bad upload file name';
            return false;
        }

        return $new_filename;
    }
}
