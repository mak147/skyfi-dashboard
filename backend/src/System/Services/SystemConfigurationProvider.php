<?php declare(strict_types=1);
namespace SkyFi\System\Services;
use SkyFi\System\Contracts\SystemConfigurationProviderContract;
use SkyFi\System\Repositories\{PdoBrandingRepository,PdoCompanyRepository,PdoLocalizationRepository,PdoNotificationPreferenceRepository,PdoSystemSettingsRepository};
final class SystemConfigurationProvider implements SystemConfigurationProviderContract
{
    private ?array $cache=null;
    public function __construct(private readonly PdoCompanyRepository $companyRepo, private readonly PdoSystemSettingsRepository $systemRepo, private readonly PdoBrandingRepository $brandingRepo, private readonly PdoLocalizationRepository $localizationRepo, private readonly PdoNotificationPreferenceRepository $notificationRepo) {}
    public function all(): array { return $this->cache ??= ['company'=>$this->company(),'system'=>$this->system(),'branding'=>$this->branding(),'localization'=>$this->localization(),'notifications'=>$this->notifications()]; }
    public function company(): array { return $this->companyRepo->first(); } public function system(): array { return $this->systemRepo->first(); } public function branding(): array { return $this->brandingRepo->first(); } public function localization(): array { return $this->localizationRepo->first(); } public function notifications(): array { return $this->notificationRepo->first(); }
    public function currencyCode(): string { return (string)($this->localization()['default_currency'] ?? $this->company()['currency_code'] ?? 'PKR'); }
    public function timezone(): string { return (string)($this->localization()['default_timezone'] ?? $this->company()['timezone'] ?? 'Asia/Karachi'); }
    public function dateFormat(): string { return (string)($this->localization()['date_format'] ?? $this->company()['date_format'] ?? 'YYYY-MM-DD'); }
    public function applicationName(): string { return (string)($this->system()['application_name'] ?? 'SkyFi ISP Management'); }
    public function fileUploadLimits(): array { $v=$this->system()['file_upload_limits'] ?? []; return is_array($v)?$v:[]; }
}
