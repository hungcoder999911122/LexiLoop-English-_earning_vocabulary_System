package com.example.englishvocab.controller;

import com.example.englishvocab.dto.ApiDtos.TopicDto;
import com.example.englishvocab.dto.ApiDtos.TopicUpsert;
import com.example.englishvocab.entity.User;
import com.example.englishvocab.service.LearningProgressService;
import com.example.englishvocab.service.TopicService;
import org.springframework.security.access.prepost.PreAuthorize;
import org.springframework.security.core.annotation.AuthenticationPrincipal;
import org.springframework.web.bind.annotation.*;

import java.util.List;
import java.util.Map;

@RestController
@RequestMapping("/api/topics")
public class TopicController {
    private final TopicService topicService;
    private final LearningProgressService progressService;

    public TopicController(TopicService topicService, LearningProgressService progressService) {
        this.topicService = topicService;
        this.progressService = progressService;
    }

    @GetMapping
    public List<TopicDto> list() {
        return topicService.listPublic();
    }

    @GetMapping("/{id}")
    public TopicDto get(@PathVariable Integer id) {
        return topicService.get(id);
    }

    @PostMapping("/{id}/enroll")
    public Map<String, String> enroll(@PathVariable Integer id, @AuthenticationPrincipal User user) {
        progressService.enroll(user, id);
        return Map.of("message", "Đã thêm từ của chủ đề vào hàng đợi ôn (SRS)");
    }

    @PostMapping
    @PreAuthorize("hasRole('ADMIN')")
    public TopicDto create(@RequestBody TopicUpsert req, @AuthenticationPrincipal User user) {
        return topicService.create(req, user);
    }

    @PutMapping("/{id}")
    @PreAuthorize("hasRole('ADMIN')")
    public TopicDto update(@PathVariable Integer id, @RequestBody TopicUpsert req) {
        return topicService.update(id, req);
    }

    @DeleteMapping("/{id}")
    @PreAuthorize("hasRole('ADMIN')")
    public void delete(@PathVariable Integer id) {
        topicService.delete(id);
    }
}
