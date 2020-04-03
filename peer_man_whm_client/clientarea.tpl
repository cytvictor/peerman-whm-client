<style>
  .table>thead>tr>th {
    text-align: center;
  }
  .table>tbody>tr>td {
    text-align: center;
    vertical-align: middle;
  }
  .bg-primary {
    padding: 12px;
  }
</style>
<div> 
  <div class="bg-primary">
    <p>
    从这里通过几个简单的步骤开始创建 BGP Session。
    </p>
    请先阅读 🙂
    <ul>
      <li>下方的表格列出了每个产品包含的 IP 地址信息。根据不同产品支持 BGP Session 的情况，请选择支持的 IP 地址来进入创建流程。</li>
      <li>当产品重置或销毁时，相关联的对等连接亦将清除，但已经验证的 IP 块和 AS 号码会保存记录，无需重复验证。如需再次配置，请重新建立对等连接。</li>
    </ul>
  </div>
<table class="table table-striped">
  <thead>
    <tr>
      <th style="width: 2em">#</th>
      <th>系列</th>
      <th>产品标识</th>
      <th>IP 地址</th>
      <th>BGP 产品</th>
      <th style="width: 20em">操作</th>
    </tr>
  </thead>
  <tbody>
  {foreach $bgp_products as $item}
    {for $i=0 to ($item['ipaddr']|count) - 1}
      {if $i == 0}
    <tr>
      <td rowspan="{$item['ipaddr']|count}">{$item['product_id']}</td>
      <td rowspan="{$item['ipaddr']|count}">{$item['product_name']}</td>
      <td rowspan="{$item['ipaddr']|count}">{$item['product_domain']}</td>
      <td><code>{$item['ipaddr'][$i]['address']}</code></td>
      <td>{if $item['ipaddr'][$i]['bgp_available']}Y{else}N{/if}</td>
      <td>{if $item['ipaddr'][$i]['bgp_available']}<button class="btn btn-xs btn-primary" onclick="requestSession(this, {$item['product_id']}, '{$item['ipaddr'][$i]['address']}');">配置 BGP Session</button>{else}-{/if}</td>
    </tr>
      {else}
    <tr>
      <td><code>{$item['ipaddr'][$i]['address']}</code></td>
      <td>{if $item['ipaddr'][$i]['bgp_available']}Y{else}N{/if}</td>
      <td>{if $item['ipaddr'][$i]['bgp_available']}<button class="btn btn-xs btn-primary" onclick="requestSession(this, {$item['product_id']}, '{$item['ipaddr'][$i]['address']}');">配置 BGP Session</button>{else}-{/if}</td>
    </tr>
      {/if}
    {/for}
  {/foreach}
  </tbody>
</table>

</div>
<script>
function requestSession(dom, product_id, ip_address) {
    dom.textContent = "请求中...";
    dom.disabled = true;
    console.log(product_id);
    console.log(ip_address);
    $.ajax({
      method: 'GET',
      url: 'index.php?m=peer_man_whm_client&action=request_authorization_url&product_id=' + product_id + '&ip_address=' + ip_address,
      success: function(res) {
        let data = JSON.parse(res)
        console.log(data);
        if (data.success) {
          dom.textContent = "已打开新窗口";
          dom.disabled =  false;
          window.open(data.bgp_configure_url, '', 'scrollbars=no,menubar=no,height=700,width=1000,left=0,top=0,screenX=0,screenY=0,resizable=no,toolbar=no,location=no,status=no');
        } else {
          dom.textContent = "错误：" + data.message;
          dom.disabled = false;
        }
      },
      error: function(err) {
        dom.textContent = "请求错误。";
        dom.disabled = false;
        setTimeout(function() {
          dom.textContent = "配置 BGP Session";
        },1000);
      }
    });
}
</script>