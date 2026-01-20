/* Country Accordion Menu JavaScript */

function setCountryOpen(country) {
    // close all
    document.querySelectorAll('.country-submenu-list').forEach(s => s.classList.remove('open'));
    document.querySelectorAll('[id^="icon-"]').forEach(i => i.textContent = '▶');

    // open selected
    const submenu = document.getElementById('submenu-' + country);
    const icon = document.getElementById('icon-' + country);
    if (submenu) submenu.classList.add('open');
    if (icon) icon.textContent = '▼';
}

function toggleCountry(country) {
    // 원-아코디언: 선택한 것만 열기
    setCountryOpen(country);
}

// 페이지 로드시 region 기준으로 항상 열림 보장
document.addEventListener('DOMContentLoaded', function () {
    const params = new URLSearchParams(window.location.search);
    const regionRaw = params.get('region') || 'korea';
    
    // Use allowed countries from PHP (if available), fallback to hardcoded
    const allowed = window.COUNTRY_REGIONS || ['korea', 'japan'];
    const region = allowed.includes(regionRaw) ? regionRaw : 'korea';
    
    setCountryOpen(region);
});
