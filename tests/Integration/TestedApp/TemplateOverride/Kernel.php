<?php

namespace TestedApp\TemplateOverride;

use TestedApp\Kernel as TestedAppKernel;

final class Kernel extends TestedAppKernel
{
    public function getProjectDir(): string
    {
        return __DIR__;
    }

    public function getCacheDir(): string
    {
        return sys_get_temp_dir().'/karross_template_override/'.$this->getEnvironment();
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir().'/karross_template_override/log';
    }
}
