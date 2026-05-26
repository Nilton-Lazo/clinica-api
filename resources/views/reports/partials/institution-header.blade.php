@php
    $inst = $meta->institution;
    $logoSrc = $inst->logoAbsolutePath ?? $inst->logoDataUri ?? $inst->logoUrl;
    $logoSizePx = 72;
@endphp
<table width="100%" cellpadding="0" cellspacing="0" style="width:100%; border-collapse:collapse; margin:0 0 12px;">
    <tr>
        @if($logoSrc)
            <td width="{{ $logoSizePx + 6 }}" valign="middle" style="width:{{ $logoSizePx + 6 }}px; padding:0 6px 0 0; border:0; vertical-align:middle;">
                <img
                    src="{{ $logoSrc }}"
                    width="{{ $logoSizePx }}"
                    height="{{ $logoSizePx }}"
                    alt=""
                    style="width:{{ $logoSizePx }}px; height:{{ $logoSizePx }}px; display:block;"
                >
            </td>
        @endif
        <td valign="middle" style="border:0; border-left:3px solid #0f4c75; padding:0 0 0 8px; vertical-align:middle;">
            <div style="font-size:11.5px; font-weight:bold; color:#0f172a; line-height:1.3;">
                {{ $inst->name }}
            </div>
            <table width="100%" cellpadding="0" cellspacing="0" style="width:100%; margin-top:6px; border-collapse:collapse; font-size:8.5px; color:#475569;">
                <tr>
                    @if($inst->ruc)
                        <td style="border:0; padding:2px 14px 2px 0; vertical-align:top;">
                            <span style="font-weight:bold; color:#64748b;">RUC:</span> {{ $inst->ruc }}
                        </td>
                    @endif
                    @if($inst->phone)
                        <td style="border:0; padding:2px 14px 2px 0; vertical-align:top;">
                            <span style="font-weight:bold; color:#64748b;">Tel.:</span> {{ $inst->phone }}
                        </td>
                    @endif
                    @if($inst->email)
                        <td style="border:0; padding:2px 0; vertical-align:top; word-wrap:break-word;">
                            <span style="font-weight:bold; color:#64748b;">Correo:</span> {{ $inst->email }}
                        </td>
                    @endif
                </tr>
                @if($inst->address || $inst->website)
                    <tr>
                        @if($inst->address)
                            <td colspan="2" style="border:0; padding:2px 14px 2px 0; vertical-align:top;">
                                <span style="font-weight:bold; color:#64748b;">Dirección:</span> {{ $inst->address }}
                            </td>
                        @endif
                        @if($inst->website)
                            <td style="border:0; padding:2px 0; vertical-align:top; word-wrap:break-word;">
                                <span style="font-weight:bold; color:#64748b;">Web:</span> {{ $inst->website }}
                            </td>
                        @endif
                    </tr>
                @endif
            </table>
        </td>
    </tr>
</table>
<table width="100%" cellpadding="0" cellspacing="0" style="width:100%; border-collapse:collapse;">
    <tr>
        <td style="height:1px; background:#cbd5e1; border:0; padding:0; line-height:1px; font-size:1px;">&nbsp;</td>
    </tr>
</table>
