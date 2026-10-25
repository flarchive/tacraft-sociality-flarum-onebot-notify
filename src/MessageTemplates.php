<?php

namespace TaCraft\FlarumOneBotNotify;

final class MessageTemplates
{
    public const NEW_DISCUSSION = "论坛来新帖子啦!\n\n帖子标题：{title}\n来自：{author}\n标签：{tags}\n{excerpt}\n链接：{url}";

    public const NEW_POST = "论坛来新回复啦!}\n\n帖子标题：{title}\n来自：{author}\n标签：{tags}\n{excerpt}\n链接：{url}";
}