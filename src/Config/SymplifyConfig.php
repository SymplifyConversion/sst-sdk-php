<?php

declare(strict_types=1);

namespace SymplifyConversion\SSTSDK\Config;

final class SymplifyConfig
{

    /** Unix timestamp when this config was last updated */
    public int $updated;

    /** The website privacy mode setting */
    public int $privacyMode;

    /** @var array<ProjectConfig> */
    public array $projects;

    /**
     * @param array<ProjectConfig> $projects
     */
    function __construct(int $updated, int $privacyMode, array $projects)
    {
        $this->updated     = $updated;
        $this->privacyMode = $privacyMode;
        $this->projects    = $projects;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromArray(array $data): SymplifyConfig
    {

        $updated     = $data['updated'] ?? 0;
        $privacyMode = $data['privacy_mode'] ?? 0;

        /** @var array<ProjectConfig> $projects */
        $projects = [];

        foreach ($data['projects'] ?? [] as $projectData) {
            $projects[] = ProjectConfig::fromArray($projectData);
        }

        return new SymplifyConfig($updated, $privacyMode, $projects);
    }

    public static function fromJSON(string $json): ?SymplifyConfig
    {
        // strip BOM, if any
        $json = ltrim($json, "\xEF\xBB\xBF");

        // depth==6 because config format has:
        // root > projects array > project object > variations array > variation object > leaf value
        $data = json_decode($json, true, 6, JSON_ERROR_SYNTAX);

        if (!$data || JSON_ERROR_NONE !== json_last_error()) {
            return null;
        }

        return self::fromArray($data);
    }

    function findProjectWithName(string $projectName): ?ProjectConfig
    {
        foreach ($this->projects as $project) {
            if ($project->name === $projectName) {
                return $project;
            }
        }

        return null;
    }

}
