<?php 
namespace Harp\lib\JWK;

use Strobotti\JWK\Key\KeyInterface;

class JwkPrototype implements KeyInterface
{
    public ?string $kty = null;
    public ?string $use = null;
    public ?string $alg = null;
    public ?string $kid = null;
    public ?string $n = null;
    public ?string $e = null;

 /**
     * @since 1.0.0
     *
     * @return false|string
     */
    public function __toString()
    {
        return \json_encode($this->jsonSerialize(), JSON_PRETTY_PRINT);
    }

    /**
     * {@inheritdoc}
     *
     * @since 1.0.0 Protected setter
     * @since 1.2.0 Public setter
     */
    public function setKeyType(string $kty): KeyInterface
    {
        $this->kty = $kty;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @since 1.0.0
     */
    public function getKeyType(): string
    {
        return $this->kty;
    }

    /**
     * {@inheritdoc}
     *
     * @since 1.2.0
     */
    public function setKeyId(?string $kid): KeyInterface
    {
        $this->kid = $kid;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @since 1.0.0
     */
    public function getKeyId(): ?string
    {
        return $this->kid;
    }

    /**
     * {@inheritdoc}
     *
     * @since 1.2.0
     */
    public function setPublicKeyUse(?string $use): KeyInterface
    {
        $this->use = $use;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @since 1.0.0
     */
    public function getPublicKeyUse(): ?string
    {
        return $this->use;
    }

    /**
     * {@inheritdoc}
     *
     * @since 1.2.0
     */
    public function setAlgorithm(string $alg): KeyInterface
    {
        $this->alg = $alg;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @since 1.0.0
     */
    public function getAlgorithm(): string
    {
        return $this->alg;
    }

    /**
     * Returns an array presentation of the key.
     *
     * @since 1.0.0
     *
     * @return array An assoc to be passed to json_encode
     */
    public function jsonSerialize(): array
    {
        $assoc = [
            'kty' => $this->kty,
            'use' => $this->use,
            'alg' => $this->alg,
        ];

        if (null !== $this->kid) {
            $assoc['kid'] = $this->kid;
        }

        return $assoc;
    }

}