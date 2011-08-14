<?php


class opProfile2CommunityPluginConfiguration extends sfPluginConfiguration
{
  public function initialize()
  {
    sfToolkit::addIncludePath(array(
      sfConfig::get('sf_lib_dir').'/vendor/',
    ));
  }
}
