package com.example.englishvocab.repository;

import com.example.englishvocab.entity.Topic;
import org.springframework.data.jpa.repository.JpaRepository;

import java.util.List;

public interface TopicRepository extends JpaRepository<Topic, Integer> {
    List<Topic> findByIsPublicTrueOrderByTopicNameAsc();
    boolean existsByTopicName(String topicName);
}
