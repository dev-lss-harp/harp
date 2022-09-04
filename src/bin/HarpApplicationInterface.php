<?php
namespace Harp\bin;

interface HarpApplicationInterface
{
    public function getName();
    public function getPublicKey();
    public function getPrivateKey();
    public function getIdentifier();
    public function getProperty($name);
    public function getProperties();
    public function isDefault();  
}
