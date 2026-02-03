<?php declare(strict_types=1);
namespace Allquanto\Datatrans\DatatransApi\Struct\Request;
use Shopware\Core\Framework\Struct\Struct;

class CustomerRequestStruct extends Struct
{
    /**
     * @var string
     */
    private string $id;

    /**
     * @var string
     */
    private string $title;

    /**
     * @var string
     */
    private string $firstName;

    /**
     * @var string
     */
    private string $lastName;

    /**
     * @var string
     */
    private string $street;

    /**
     * @var string|null
     */
    private ?string $street2;

    /**
     * @var string
     */
    private string $city;

    /**
     * @var string
     */
    private string $country;

    /**
     * @var string
     */
    private string $zipCode;

    /**
     * @var string
     */
    private string $phone;

    /**
     * @var string|null
     */
    private ?string $cellPhone;

    /**
     * @var string
     */
    private string $email;

    /**
     * @var string|null
     */
    private ?string $gender;

    /**
     * @var string|null
     */
    private ?string $birthDate;

    /**
     * @var string
     */
    private string $language;

    /**
     * @var string|null
     */
    private ?string $type;

    /**
     * @var string|null
     */
    private ?string $name;

    /**
     * @var string|null
     */
    private ?string $companyLegalForm;

    /**
     * @var string|null
     */
    private ?string $companyRegisterNumber;

    /**
     * @var string|null
     */
    private ?string $ipAddress;

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @param string $id
     */
    public function setId(string $id): void
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getFirstName(): string
    {
        return $this->firstName;
    }

    /**
     * @param string $firstName
     */
    public function setFirstName(string $firstName): void
    {
        $this->firstName = $firstName;
    }

    /**
     * @return string
     */
    public function getLastName(): string
    {
        return $this->lastName;
    }

    /**
     * @param string $lastName
     */
    public function setLastName(string $lastName): void
    {
        $this->lastName = $lastName;
    }

    /**
     * @return string
     */
    public function getStreet(): string
    {
        return $this->street;
    }

    /**
     * @param string $street
     */
    public function setStreet(string $street): void
    {
        $this->street = $street;
    }

    /**
     * @return string|null
     */
    public function getStreet2(): ?string
    {
        return $this->street2;
    }

    /**
     * @param string|null $street2
     */
    public function setStreet2(?string $street2): void
    {
        $this->street2 = $street2;
    }

    /**
     * @return string
     */
    public function getCity(): string
    {
        return $this->city;
    }

    /**
     * @param string $city
     */
    public function setCity(string $city): void
    {
        $this->city = $city;
    }

    /**
     * @return string
     */
    public function getCountry(): string
    {
        return $this->country;
    }

    /**
     * @param string $country
     */
    public function setCountry(string $country): void
    {
        $this->country = $country;
    }

    /**
     * @return string
     */
    public function getZipCode(): string
    {
        return $this->zipCode;
    }

    /**
     * @param string $zipCode
     */
    public function setZipCode(string $zipCode): void
    {
        $this->zipCode = $zipCode;
    }

    /**
     * @return string
     */
    public function getPhone(): string
    {
        return $this->phone;
    }

    /**
     * @param string $phone
     */
    public function setPhone(string $phone): void
    {
        $this->phone = $phone;
    }

    /**
     * @return string|null
     */
    public function getCellPhone(): ?string
    {
        return $this->cellPhone;
    }

    /**
     * @param string|null $cellPhone
     */
    public function setCellPhone(?string $cellPhone): void
    {
        $this->cellPhone = $cellPhone;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    /**
     * @return string|null
     */
    public function getGender(): ?string
    {
        return $this->gender;
    }

    /**
     * @param string|null $gender
     */
    public function setGender(?string $gender): void
    {
        $this->gender = $gender;
    }

    /**
     * @return string|null
     */
    public function getBirthDate(): ?string
    {
        return $this->birthDate;
    }

    /**
     * @param string|null $birthDate
     */
    public function setBirthDate(?string $birthDate): void
    {
        $this->birthDate = $birthDate;
    }

    /**
     * @return string
     */
    public function getLanguage(): string
    {
        return $this->language;
    }

    /**
     * @param string $language
     */
    public function setLanguage(string $language): void
    {
        $this->language = $language;
    }

    /**
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @param string|null $type
     */
    public function setType(?string $type): void
    {
        $this->type = $type;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string|null $name
     */
    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string|null
     */
    public function getCompanyLegalForm(): ?string
    {
        return $this->companyLegalForm;
    }

    /**
     * @param string|null $companyLegalForm
     */
    public function setCompanyLegalForm(?string $companyLegalForm): void
    {
        $this->companyLegalForm = $companyLegalForm;
    }

    /**
     * @return string|null
     */
    public function getCompanyRegisterNumber(): ?string
    {
        return $this->companyRegisterNumber;
    }

    /**
     * @param string|null $companyRegisterNumber
     */
    public function setCompanyRegisterNumber(?string $companyRegisterNumber): void
    {
        $this->companyRegisterNumber = $companyRegisterNumber;
    }

    /**
     * @return string|null
     */
    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    /**
     * @param string|null $ipAddress
     */
    public function setIpAddress(?string $ipAddress): void
    {
        $this->ipAddress = $ipAddress;
    }

    public function jsonSerialize(): array
    {
        $vars = get_object_vars($this);
        unset($vars['extensions']);

        return $vars;
    }

}
