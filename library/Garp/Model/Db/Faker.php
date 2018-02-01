<?php
use Garp\Functional as f;

/**
 * Garp_Model_Db_Faker
 * Faker implementation that interprets our Spawner specific field configuration and provides
 * sensible default data
 *
 * @package Garp_Model_Db
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Model_Db_Faker {

    /**
     * @var Faker\Generator
     */
    protected $_faker;

    public function __construct() {
        $this->_faker = Faker\Factory::create();
    }

    /**
     * Create a row with fake values
     *
     * @param array $fieldConfiguration As taken from Garp_Model_Db::getConfiguration()
     * @param array $defaultValues      Any values you want to provide yourself
     * @return array
     */
    public function createFakeRow(array $fieldConfiguration, array $defaultValues = array()) {
        // TODO For now, filter primary keys, assuming they will be auto-generated by the database.
        $configWithoutPks = f\filter(not(array_get('primary')), $fieldConfiguration);
        $self = $this;
        return f\reduce(
            function ($out, $field) use ($self) {
                $fieldName = $field['name'];
                if (array_key_exists($fieldName, $out)) {
                    return $out;
                }
                $out[$fieldName] = $self->getFakeValueForField($field);
                return $out;
            },
            $defaultValues,
            $configWithoutPks
        );
    }

    /**
     * Get single fake value for a field configuration.
     *
     * @param array $config Configuration as taken from Garp_Model_Db::getFieldConfiguration()
     * @return mixed
     */
    public function getFakeValueForField(array $config) {
        if (!array_get($config, 'required')) {
            // Give optional fields a 10% chance (more or less) to be NULL
            $diceRoll = $this->_faker->numberBetween(1, 100);
            if ($diceRoll < 10) {
                return null;
            }
        }

        if (array_get($config, 'origin') === 'relation') {
            // TODO Do something intelligent here?
            // I don't want to tightly couple this class to a database or model, but a random
            // integer for a foreign key will most definitely result in an
            // Integrity constraint violation when there's no seed data.
            return null;
        }

        if ($config['type'] === 'text') {
            return $this->_getFakeText($config);
        }

        if ($config['type'] === 'html') {
            $value = $this->_faker->randomHtml(2, 3);
            $htmlFilterable = new Garp_Model_Behavior_HtmlFilterable();
            return $htmlFilterable->filter($value);
        }

        if ($config['type'] === 'enum' || $config['type'] === 'set') {
            $options = $config['options'];
            $isAssoc = f\every('is_string', array_keys($options));
            $randomPool = $isAssoc ? array_keys($options) : array_values($options);
            return $this->_faker->randomElement($randomPool);
        }

        if ($config['type'] === 'email') {
            return $this->_faker->email;
        }

        if ($config['type'] === 'url') {
            return $this->_faker->url;
        }

        if ($config['type'] === 'checkbox') {
            return $this->_faker->randomElement(array(0, 1));
        }

        if ($config['type'] === 'date' || $config['type'] === 'datetime') {
            $value = $this->_faker->dateTimeBetween('-1 year', '+1 year');
            $format = $config['type'] === 'date' ? 'Y-m-d' : 'Y-m-d H:i:s';
            return $value->format($format);
        }

        if ($config['type'] === 'time') {
            return $this->_faker->time;
        }

        if ($config['type'] === 'imagefile') {
            return 'image.jpg';
        }

        if ($config['type'] === 'document') {
            return 'document.txt';
        }

        if ($config['type'] === 'numeric') {
            return $this->_faker->randomDigit;
        }

        throw new InvalidArgumentException("Unknown type encountered: {$config['type']}");
    }

    protected function _getFakeText(array $config) {
        $name = $config['name'];
        if ($name === 'name') {
            return $this->_faker->sentence;
        }
        if ($name === 'first_name') {
            return $this->_faker->firstName;
        }
        if ($name === 'last_name') {
            return $this->_faker->lastName;
        }
        return $this->_faker->realText(
            $this->_faker->numberBetween(
                max(10, array_get($config, 'minLength', 10)),
                max(10, array_get($config, 'maxLength', 255))
            )
        );
    }
}


