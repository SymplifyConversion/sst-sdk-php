<?php

declare(strict_types=1);

namespace Symplify\SSTSDK\Config;

class SymplifyConfig
{
    /** @var int Unix timestamp when this config was last updated */
    public $updated;
    /** @var ProjectConfig[] */
    public $projects;

    /**
     * @param int $updated
     * @param ProjectConfig[] $projects
     */
    function __construct($updated, $projects)
    {
        $this->updated = $updated;
        $this->projects = $projects;
    }

    /**
     * @param mixed $data
     */
    public static function fromArray($data): SymplifyConfig
    {

        $updated = $data['updated'] ?? 0;

        /** @var ProjectConfig[] */
        $projects = [];
        foreach ($data['projects'] ?? [] as $projectData) {
            $projects[] = ProjectConfig::fromArray($projectData);
        }

        return new SymplifyConfig($updated, $projects);
    }

    public static function fromJSON(string $json): ?SymplifyConfig
    {
        // depth==6 because config format has:
        // root > projects array > project object > variations array > variation object > leaf value
        $data = json_decode($json, true, 6, JSON_ERROR_SYNTAX) ?? [];
        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }
        return self::fromArray($data);
    }
}
