<table width="100%" align="center" border="0" cellspacing="0" cellpadding="0"
       style="width:100%;min-height:100%;letter-spacing: 0.02em;padding:20px 6px;font-size:14px;border-spacing:0;"
>
<tbody><tr><td>
    <table width="90%" align="center" border="0" cellspacing="0" cellpadding="0"
           style="width: 90%;max-width: 600px;text-align:left;">
        <tbody>
        <tr>
            <td style="font-family: Arial, sans-serif">

                <div style="font-size:16px;font-weight:700;color: #5262af;">
                    Thank you for your purchase, {{$username}}.
                </div>

                <div style="padding: 6px 0 14px 0; font-size: 14px; color: #525252; letter-spacing: normal;">
                    {{parseTime($time,'UTC','dateTime',0,1)}} UTC
                </div>

                <div style="padding: 12px 0 12px 0;border-top:1px solid #b1b1b1;text-align:left">
                    <table style="width: 100%; color:#1b1b1b; font-size:15px;">

                        <thead>
                        <tr>
                            <th title="Purchase ID" align="left">Sale ID</th>
                            <th title="Item Name" align="left">Item name</th>
                            <th title="Item Price" align="left">Price</th>
                        </tr>
                        </thead>

                        <tbody>

                        @foreach ($sales as $sale)

                            <tr style="padding: 4px 0; text-align: left;">
                                <td style="padding-right:10px">{{$sale['sale_id']}}</td>
                                <td style="padding-right:10px">{!! $sale['name'] !!}</td>
                                <td style="padding-right:10px">{{priceOutput($sale['price'])}}</td>
                            </tr>

                        @endforeach

                        </tbody>

                    </table>

                </div>

                <div style="padding:10px 0;font-size:14px;font-weight:700;color: #0c0c0c;border-top:1px solid #b1b1b1;">
                    Grand Total: {{priceOutput($purchase_amount)}}
                    <br>
                    <div style="padding-top: 10px;">
                        <a href="{{config('app.url')}}" style="font-size:15px; color: #5262af">{{config('app.name')}}</a>
                    </div>
                </div>

            </td>
        </tr>
        </tbody>
    </table>

</td></tr></tbody></table>