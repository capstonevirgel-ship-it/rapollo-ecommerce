<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Rapollo Email')</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            width: 100%;
            background-color: #f4f4f5;
            color: #18181b;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            line-height: 1.6;
        }

        table {
            border-spacing: 0;
            border-collapse: collapse;
        }

        img {
            border: 0;
            line-height: 100%;
            text-decoration: none;
            max-width: 100%;
        }

        .header-title {
            font-family: 'Times New Roman', Times, serif !important;
            letter-spacing: 0.14em;
        }

        .footer-title {
            font-family: 'Times New Roman', Times, serif !important;
            letter-spacing: 0.1em;
        }

        .content-shell {
            width: 100%;
            max-width: 640px;
        }

        @media only screen and (max-width: 640px) {
            .stack-column {
                display: block !important;
                width: 100% !important;
            }

            .content-padding {
                padding-left: 24px !important;
                padding-right: 24px !important;
            }
        }
    </style>
    @stack('styles')
</head>
<body>
    <center style="width: 100%; background-color: #f4f4f5;">
        <table role="presentation" width="100%" style="background-color: #f4f4f5;">
            <tr>
                <td align="center" style="padding: 32px 16px;">
                    <table role="presentation" width="100%" class="content-shell" style="max-width: 640px; background-color: #ffffff; border-radius: 18px; overflow: hidden; border: 1px solid #e4e4e7;">
                        @hasSection('header')
                            <tr>
                                <td align="center" style="background-color: #18181b; padding: 40px 32px;">
                                    <table role="presentation" width="100%">
                                        <tr>
                                            <td align="center" class="header-title" style="color: #f4f4f5; text-transform: uppercase; font-weight: 700; font-size: 26px;">
                                                @yield('header')
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        @endif

                        <tr>
                            <td class="content-padding" style="padding: 40px 36px;">
                                @yield('content')
                            </td>
                        </tr>

                        @hasSection('footer')
                            <tr>
                                <td align="center" style="background-color: #f9fafb; padding: 32px 28px; border-top: 1px solid #e4e4e7;">
                                    <table role="presentation" width="100%">
                                        <tr>
                                            <td align="center" class="footer-title" style="color: #27272a; font-size: 14px; text-transform: uppercase; font-weight: 600;">
                                                @yield('footer')
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        @endif
                    </table>
                </td>
            </tr>
        </table>
    </center>
</body>
</html>


