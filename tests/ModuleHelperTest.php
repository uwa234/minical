<?php

use PHPUnit\Framework\TestCase;

class ModuleHelperTest extends TestCase
{
    public function testIsModuleDirectoryRejectsDotEntries()
    {
        $this->assertFalse(is_module_directory(sys_get_temp_dir(), '.'));
        $this->assertFalse(is_module_directory(sys_get_temp_dir(), '..'));
    }

    public function testIsModuleDirectoryDetectsExistingDirectory()
    {
        $base = sys_get_temp_dir() . '/minical_module_test_' . uniqid();
        mkdir($base);
        mkdir($base . '/sample_extension');

        $this->assertTrue(is_module_directory($base, 'sample_extension'));
        $this->assertFalse(is_module_directory($base, 'missing_extension'));

        rmdir($base . '/sample_extension');
        rmdir($base);
    }
}
