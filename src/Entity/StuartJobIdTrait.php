<?php

/*
 * This file is part of Monsieur Biz' Stuart delivery plugin for Sylius.
 *
 * (c) Monsieur Biz <sylius@monsieurbiz.com>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MonsieurBiz\SyliusStuartDeliveryPlugin\Entity;

use Doctrine\ORM\Mapping as ORM;

trait StuartJobIdTrait
{
    /**
     * @ORM\Column(name="stuart_job_id", type="integer", unique=true, nullable=true)
     */
    protected ?int $stuartJobId = null;

    public function getStuartJobId(): ?int
    {
        return $this->stuartJobId;
    }

    public function setStuartJobId(?int $stuartJobId): void
    {
        $this->stuartJobId = $stuartJobId;
    }
}
