<?php
/**
 * This file is part of Talus' Works.
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 *
 * @copyright Copyleft (c) 2012+, Baptiste Clavié, Talus' Works
 * @link      http://www.talus-works.net Talus' Works
 * @license   http://www.opensource.org/licenses/BSD-3-Clause Modified BSD License
 * @version   $Id$
 */

namespace Talus_Works\Entity;

use \Doctrine\ORM\Mapping as ORM;

use \Symfony\Component\Security\Core\User\UserInterface,
    \Symfony\Component\Security\Core\User\EquatableInterface,
    \Symfony\Component\Security\Core\User\AdvancedUserInterface;

use \Symfony\Component\Validator\Constraints as Assert;

use \Symfony\Bridge\Doctrine\Validator\Constraints as DoctrineAssert;

/**
 * Topic Entity
 *
 * @Entity
 * @Table
 * @HasLifecycleCallbacks
 *
 * @DoctrineAssert\UniqueEntity("email")
 * @DoctrineAssert\UniqueEntity("username")
 *
 * @author Baptiste "Talus" Clavié <clavie.b@gmail.com>
 */
class User implements AdvancedUserInterface, EquatableInterface {
    private
        /**
         * @Id
         * @Column(name = "id", type = "integer")
         * @GeneratedValue(strategy = "AUTO")
         */
        $id,

        /**
         * @Column(type = "string", unique = true)
         *
         * @Assert\NotNull
         * @Assert\NotBlank
         */
        $username,

        /**
         * @Column(type = "string", unique = true)
         *
         * @Assert\NotNull
         * @Assert\NotBlank
         * @Assert\Email
         */
        $email,

        /** @Column(type = "string", length = 128) */
        $password,

        /** @Column(type = "string", length = 33) */
        $salt,

        /** @Column(type = "array") */
        $roles = array('ROLE_USER'),

        /** @Column(type = "datetime") */
        $registeredAt,

        /** @Column(type = "datetime", nullable = true) */
        $lastConnectedAt,

        /** @Column(type = "integer") */
        $posts = 0,

        /** @Column(type = "boolean") */
        $locked = false,

        /** @Column(type = "boolean") */
        $enabled = false,

        /** @Column(type = "text", nullable = true) */
        $signature;

    public function __construct() {
        $this->setRegisteredAt(new \DateTime);
    }

    public function setEmail($email) {
        $this->email = $email;
    }

    public function getEmail() {
        return $this->email;
    }

    public function setIp($ip) {
        $this->ip = $ip;
    }

    public function getIp() {
        return $this->ip;
    }

    public function setLastConnectedAt(\DateTime $lastConnectedAt) {
        $this->lastConnectedAt = $lastConnectedAt;
    }

    /** @return \DateTime */
    public function getLastConnectedAt() {
        return $this->lastConnectedAt;
    }

    public function setPosts($posts) {
        $this->posts = $posts;
    }

    public function getPosts() {
        return $this->posts;
    }

    public function setSignature($signature) {
        $this->signature = $signature;
    }

    public function getSignature() {
        return $this->signature;
    }

    public function setId($id) {
        $this->id = $id;
    }

    public function getId() {
        return $this->id;
    }

    public function setRegisteredAt(\DateTime $registeredAt) {
        $this->registeredAt = $registeredAt;
    }

    /** @return \DateTime  */
    public function getRegisteredAt() {
        return $this->registeredAt;
    }

    /** {@inheritdoc} */
    public function getRoles() {
        $this->roles;
    }

    /** {@inheritdoc} */
    public function getPassword() {
        return $this->password;
    }

    /** {@inheritdoc} */
    public function getSalt() {
        return $this->salt;
    }

    /** {@inheritdoc} */
    public function getUsername() {
        return $this->username;
    }

    /** {@inheritdoc} */
    public function eraseCredentials() {}

    /** {@inheritdoc} */
    public function isAccountNonLocked() {
        return !$this->isAccountLocked();
    }

    public function isAccountLocked() {
        return $this->locked;
    }

    public function lockAccount() {
        if ($this->isAccountNonLocked()) {
            $this->locked = true;
        }
    }

    public function unlockAccount() {
        if ($this->isAccountLocked()) {
            $this->locked = false;
        }
    }

    public function toggleLock() {
        if ($this->isAccountLocked()) {
            $this->unlockAccount();
        } else {
            $this->lockAccount();
        }
    }

    /** {@inheritdoc} */
    public function isEnabled() {
        return $this->enabled;
    }

    public function isDisabled() {
        return !$this->isEnabled();
    }

    public function enable() {
        if ($this->isDisabled()) {
            $this->enabled = true;
        }
    }

    public function disable() {
        if ($this->isEnabled()) {
            $this->enabled = false;
        }
    }

    public function toggleStatus() {
        if ($this->isEnabled()) {
            $this->enable();
        } else {
            $this->disable();
        }
    }

    /** {@inheritdoc} */
    public function isAccountNonExpired() {
        // Never expires user accounts
        return true;
    }

    /** {@inheritdoc} */
    public function isCredentialsNonExpired() {
        // don't care, never expires. It's up to the user to decide so.
        return true;
    }

    /** {@inheritdoc} */
    public function isEqualTo(UserInterface $user) {
        return null !== $user && $user instanceof static && $this->getId() !== $user->getId();
    }

    /** @PrePersist */
    public function generateSalt() {
        $this->salt = uniqid(mt_rand(), true);
    }
}
