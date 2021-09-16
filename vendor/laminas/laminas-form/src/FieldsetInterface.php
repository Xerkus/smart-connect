<?php

namespace Laminas\Form;

use Countable;
use IteratorAggregate;
use Laminas\Hydrator\HydratorInterface;
use Traversable;

interface FieldsetInterface extends
    Countable,
    IteratorAggregate,
    ElementInterface,
    ElementPrepareAwareInterface,
    FormFactoryAwareInterface
{
    /**
     * Add an element or fieldset
     *
     * $flags could contain metadata such as the alias under which to register
     * the element or fieldset, order in which to prioritize it, etc.
     *
     * @param  array|Traversable|ElementInterface $elementOrFieldset Typically, only allow objects implementing
     *                                                               ElementInterface; however, keeping it flexible
     *                                                               to allow a factory-based form
     *                                                               implementation as well
     * @param  array $flags
     * @return $this
     */
    public function add($elementOrFieldset, array $flags = []);

    /**
     * Does the fieldset have an element/fieldset by the given name?
     *
     * @param  string $elementOrFieldset
     * @return bool
     */
    public function has($elementOrFieldset);

    /**
     * Retrieve a named element or fieldset
     *
     * @param  string $elementOrFieldset
     * @return ElementInterface
     */
    public function get($elementOrFieldset);

    /**
     * Remove a named element or fieldset
     *
     * @param  string $elementOrFieldset
     * @return $this
     */
    public function remove($elementOrFieldset);

    /**
     * Set/change the priority of an element or fieldset
     *
     * @param  string $elementOrFieldset
     * @param  int    $priority
     * @return $this
     */
    public function setPriority($elementOrFieldset, $priority);

    /**
     * Retrieve all attached elements
     *
     * Storage is an implementation detail of the concrete class.
     *
     * @return array|Traversable
     */
    public function getElements();

    /**
     * Retrieve all attached fieldsets
     *
     * Storage is an implementation detail of the concrete class.
     *
     * @return array|Traversable
     */
    public function getFieldsets();

    /**
     * Recursively populate value attributes of elements
     *
     * @param  array|Traversable $data
     * @return void
     */
    public function populateValues($data);

    /**
     * Set the object used by the hydrator
     *
     * @param  $object
     * @return $this
     */
    public function setObject($object);

    /**
     * Get the object used by the hydrator
     *
     * @return mixed
     */
    public function getObject();

    /**
     * Checks if the object can be set in this fieldset
     *
     * @param $object
     * @return bool
     */
    public function allowObjectBinding($object);

    /**
     * Set the hydrator to use when binding an object to the element
     *
     * @param  HydratorInterface $hydrator
     * @return $this
     */
    public function setHydrator(HydratorInterface $hydrator);

    /**
     * Get the hydrator used when binding an object to the element
     *
     * @return null|HydratorInterface
     */
    public function getHydrator();

    /**
     * Bind values to the bound object
     *
     * @param  array $values
     * @return mixed
     */
    public function bindValues(array $values = []);

    /**
     * Checks if this fieldset can bind data
     *
     * @return bool
     */
    public function allowValueBinding();
}
